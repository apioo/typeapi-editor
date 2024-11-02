<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright 2010-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace TypeAPI\Editor;

use PSX\Record\Record;
use TypeAPI\Editor\Exception\GeneratorException;
use TypeAPI\Editor\Model\Document;
use TypeAPI\Editor\Model\Operation;
use TypeAPI\Editor\Model\Property;
use TypeAPI\Editor\Model\Security;
use TypeAPI\Editor\Model\Type;
use TypeAPI\Model;

/**
 * Generator which transforms a document provided from an editor to an actual TypeSchema specification
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Generator
{
    /**
     * Generates a TypeSchema specification based on the document
     *
     * @throws GeneratorException
     */
    public function generate(Document $document, ?string $baseUrl = null): string
    {
        return \json_encode($this->toModel($document, $baseUrl), \JSON_PRETTY_PRINT);
    }

    /**
     * @throws GeneratorException
     */
    public function toModel(Document $document, ?string $baseUrl = null): Model\TypeAPI
    {
        $schema = new Model\TypeAPI();

        $documentBaseUrl = $document->getBaseUrl();
        if (!empty($documentBaseUrl)) {
            $schema->setBaseUrl($documentBaseUrl);
        } elseif (!empty($baseUrl)) {
            $schema->setBaseUrl($baseUrl);
        }

        $security = $this->generateSecurity($document->getSecurity());
        if ($security instanceof Model\Security) {
            $schema->setSecurity($security);
        }

        $import = $this->generateImport($document->getImports());
        if ($import instanceof Record) {
            $schema->setImport($import);
        }

        /** @var Record<Model\Operation> $operations */
        $operations = new Record();
        foreach ($document->getOperations() as $operation) {
            $operations->put($operation->getName(), $this->generateOperation($operation));
        }
        $schema->setOperations($operations);

        /** @var Record<Model\DefinitionType> $definitions */
        $definitions = new Record();
        $types = $document->getTypes();
        foreach ($types as $type) {
            $typeName = $type->getName();
            if (empty($typeName)) {
                continue;
            }

            $definitions->put($typeName, $this->generateDefinitionType($type));
        }
        $schema->setDefinitions($definitions);

        $root = $document->getRoot();
        if ($root !== null && isset($types[$document->getRoot()])) {
            $rootType = $types[$root] ?? null;
            if ($rootType instanceof Type) {
                $schema->setRoot($rootType->getName());
            }
        }

        return $schema;
    }

    /**
     * @return Record<string>|null
     */
    private function generateImport(?array $imports): ?Record
    {
        if (empty($imports)) {
            return null;
        }

        /** @var Record<string> $result */
        $result = new Record();
        foreach ($imports as $import) {
            $alias = $import->getAlias() ?? null;
            $user = $import->getDocument()->user->name ?? null;
            $document = $import->getDocument()->name ?? null;
            $version = $import->getVersion() ?? null;

            if (empty($alias) || empty($user) || empty($document) || empty($version)) {
                continue;
            }

            $result->put($alias, 'typehub://' . $user . ':' . $document . '@' . $version);
        }

        return $result;
    }

    private function generateSecurity(?Security $security): ?Model\Security
    {
        if (empty($security)) {
            return null;
        }

        if ($security->getType() === 'httpBasic') {
            $return = new Model\SecurityHttpBasic();
            $return->setType('httpBasic');
        } elseif ($security->getType() === 'httpBearer') {
            $return = new Model\SecurityHttpBearer();
            $return->setType('httpBearer');
        } elseif ($security->getType() === 'apiKey') {
            $return = new Model\SecurityApiKey();
            $return->setType('apiKey');
            $return->setIn($security->getIn());
            $return->setName($security->getName());
        } elseif ($security->getType() === 'oauth2') {
            $return = new Model\SecurityOAuth();
            $return->setType('oauth2');
            $return->setTokenUrl($security->getTokenUrl());
            $return->setAuthorizationUrl($security->getAuthorizationUrl());
            $return->setScopes($security->getScopes());
        } else {
            return null;
        }

        return $return;
    }

    /**
     * @throws GeneratorException
     */
    private function generateOperation(Operation $operation): Model\Operation
    {
        $result = new Model\Operation();

        if ($operation->getDescription() !== null) {
            $result->setDescription($operation->getDescription());
        }

        if ($operation->getHttpMethod() !== null) {
            $result->setMethod($operation->getHttpMethod());
        }

        if ($operation->getHttpPath() !== null) {
            $result->setPath($operation->getHttpPath());
        }

        /** @var Record<Model\Argument> $args */
        $args = new Record();
        $legacyPayload = null;
        if (count($operation->getArguments()) > 0) {
            foreach ($operation->getArguments() as $argument) {
                if ($argument->getIn() === 'body') {
                    $legacyPayload = $argument;
                    continue;
                }

                $name = $argument->getName();
                if (empty($name)) {
                    continue;
                }

                $args->put($name, $this->generateArgument(
                    $argument->getIn() ?? throw new GeneratorException('Argument no in provided'),
                    $argument->getType() ?? throw new GeneratorException('Argument no type provided')
                ));
            }
        }

        $payload = $operation->getPayload();
        if ($payload !== null && in_array($operation->getHttpMethod(), ['POST', 'PUT', 'PATCH'])) {
            $args->put('payload', $this->generateArgumentBody(
                $payload,
                $operation->getPayloadShape()
            ));
        } elseif ($legacyPayload !== null) {
            $payloadType = $legacyPayload->getType();
            if (!empty($payloadType)) {
                $args->put('payload', $this->generateArgumentBody($payloadType));
            }
        }

        if (!$args->isEmpty()) {
            $result->setArguments($args);
        }

        if (count($operation->getThrows()) > 0) {
            $throws = [];
            foreach ($operation->getThrows() as $throw) {
                $throws[] = $this->generateResponse(
                    $throw->getCode() ?? 500,
                    $throw->getType() ?? throw new GeneratorException('Throw no type provided'),
                    $throw->getTypeShape()
                );
            }
            $result->setThrows($throws);
        }

        $httpCode = $operation->getHttpCode() ?? 200;
        if ($httpCode === 204) {
            $result->setReturn($this->generateResponse($httpCode, ''));
        } else {
            $return = $operation->getReturn();
            if ($return !== null) {
                $result->setReturn($this->generateResponse($httpCode, $return, $operation->getReturnShape()));
            }
        }

        if ($operation->getStability() !== null) {
            $result->setStability($operation->getStability());
        }

        if ($operation->getSecurity() !== null) {
            $result->setSecurity($operation->getSecurity());
        }

        if ($operation->getAuthorization() !== null) {
            $result->setAuthorization($operation->getAuthorization());
        }

        if ($operation->getTags() !== null) {
            $result->setTags($operation->getTags());
        }

        return $result;
    }

    private function generateArgument(string $in, string $type): Model\Argument
    {
        $result = new Model\Argument();
        $result->setIn($in);
        $result->setSchema($this->resolveReferenceType($type));
        return $result;
    }

    private function generateArgumentBody(string $type, ?string $typeShape = null): Model\Argument
    {
        $result = new Model\Argument();
        $result->setIn('body');
        $result->setSchema($this->getTypeShape($type, $typeShape));
        return $result;
    }

    private function generateResponse(int $httpCode, string $return, ?string $returnShape = null): Model\Response
    {
        if ($httpCode === 204) {
            $schema = new Model\AnyPropertyType();
            $schema->setType('any');
        } else {
            $schema = $this->getTypeShape($return, $returnShape);
        }

        $result = new Model\Response();
        $result->setCode($httpCode);
        $result->setSchema($schema);
        return $result;
    }

    private function getTypeShape(string $type, ?string $typeShape = null): Model\PropertyType
    {
        $reference = new Model\ReferencePropertyType();
        $reference->setType('reference');
        $reference->setTarget($type);

        if ($typeShape === Type::TYPE_ARRAY) {
            $return = new Model\ArrayPropertyType();
            $return->setType('array');
            $return->setSchema($reference);
            return $return;
        } elseif ($typeShape === Type::TYPE_MAP) {
            $return = new Model\MapPropertyType();
            $return->setType('map');
            $return->setSchema($reference);
            return $return;
        } else {
            return $reference;
        }
    }

    /**
     * @throws GeneratorException
     */
    private function generateDefinitionType(Type $type): Model\DefinitionType
    {
        if ($type->getType() === Type::TYPE_MAP) {
            $result = new Model\MapDefinitionType();
            $result->setType('map');
            $result->setSchema($this->resolveReferenceType($type->getReference()));
        } elseif ($type->getType() === Type::TYPE_ARRAY) {
            $result = new Model\ArrayDefinitionType();
            $result->setType('array');
            $result->setSchema($this->resolveReferenceType($type->getReference()));
        } else {
            $result = new Model\StructDefinitionType();
            $result->setType('struct');

            if ($type->getParent() !== null) {
                $parent = new Model\ReferencePropertyType();
                $parent->setType('reference');
                $parent->setTarget($type->getParent());
                $template = $type->getTemplate();
                if (!empty($template)) {
                    $parent->setTemplate(Record::from($template));
                }
                $result->setParent($parent);
            }

            if (count($type->getProperties()) > 0) {
                /** @var Record<Model\PropertyType> $props */
                $props = new Record();
                foreach ($type->getProperties() as $property) {
                    $name = $property->getName();
                    if (empty($name)) {
                        continue;
                    }

                    $props->put($name, $this->generatePropertyType($property));
                }
                $result->setProperties($props);
            }
        }

        if ($type->getDescription() !== null) {
            $result->setDescription($type->getDescription());
        }

        return $result;
    }

    /**
     * @throws GeneratorException
     */
    private function generatePropertyType(Property $property): Model\PropertyType
    {
        if ($property->getType() === Property::TYPE_OBJECT) {
            $result = new Model\ReferencePropertyType();
            $result->setType('reference');
            $result->setTarget($property->getReference());
        } elseif ($property->getType() === Property::TYPE_MAP) {
            $result = new Model\MapPropertyType();
            $result->setType('map');
            $result->setSchema($this->resolveReferenceType($property->getReference(), $property->getGeneric(), $property->getFormat()));
        } elseif ($property->getType() === Property::TYPE_ARRAY) {
            $result = new Model\ArrayPropertyType();
            $result->setType('array');
            $result->setSchema($this->resolveReferenceType($property->getReference(), $property->getGeneric(), $property->getFormat()));
        } elseif ($property->getType() === Property::TYPE_STRING) {
            $result = new Model\StringPropertyType();
            $result->setType('string');

            if ($property->getFormat() !== null) {
                $result->setFormat($property->getFormat());
            }
        } elseif ($property->getType() === Property::TYPE_INTEGER) {
            $result = new Model\IntegerPropertyType();
            $result->setType('integer');
        } elseif ($property->getType() === Property::TYPE_NUMBER) {
            $result = new Model\NumberPropertyType();
            $result->setType('number');
        } elseif ($property->getType() === Property::TYPE_BOOLEAN) {
            $result = new Model\BooleanPropertyType();
            $result->setType('boolean');
        } elseif ($property->getType() === Property::TYPE_ANY) {
            $result = new Model\AnyPropertyType();
            $result->setType('any');
        } elseif ($property->getType() === Property::TYPE_GENERIC) {
            $result = new Model\GenericPropertyType();
            $result->setType('generic');
            $result->setName($property->getGeneric());
        } else {
            throw new GeneratorException('Provided an invalid property type');
        }

        if ($property->getDescription() !== null) {
            $result->setDescription($property->getDescription());
        }

        return $result;
    }

    /**
     * @throws GeneratorException
     */
    private function resolveReferenceType(?string $reference, ?string $generic = null, ?string $format = null): Model\PropertyType
    {
        if (empty($reference)) {
            throw new GeneratorException('Reference must contain a string');
        }

        if ($reference === 'string') {
            $return = new Model\StringPropertyType();
            $return->setType('string');
            if (!empty($format)) {
                $return->setFormat($format);
            }
        } elseif ($reference === 'integer') {
            $return = new Model\IntegerPropertyType();
            $return->setType('integer');
        } elseif ($reference === 'number') {
            $return = new Model\NumberPropertyType();
            $return->setType('number');
        } elseif ($reference === 'boolean') {
            $return = new Model\BooleanPropertyType();
            $return->setType('boolean');
        } elseif ($reference === 'any') {
            $return = new Model\AnyPropertyType();
            $return->setType('any');
        } elseif ($reference === 'generic') {
            $return = new Model\GenericPropertyType();
            $return->setType('generic');
            $return->setName($generic);
        } else {
            $return = new Model\ReferencePropertyType();
            $return->setType('reference');
            $return->setTarget($reference);
        }

        return $return;
    }
}

