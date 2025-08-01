<?php
/*
 * PSX is an open source PHP framework to develop RESTful APIs.
 * For the current version and information visit <https://phpsx.org>
 *
 * Copyright (c) Christoph Kappestein <christoph.kappestein@gmail.com>
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

use PSX\Schema\Exception\InvalidSchemaException;
use PSX\Schema\SchemaManagerInterface;
use PSX\Schema\SchemaSource;
use TypeAPI\Editor\Exception\ParserException;
use TypeAPI\Editor\Model\Argument;
use TypeAPI\Editor\Model\Document;
use TypeAPI\Editor\Model\Error;
use TypeAPI\Editor\Model\Import;
use TypeAPI\Editor\Model\Operation;
use TypeAPI\Editor\Model\Property;
use TypeAPI\Editor\Model\Security;
use TypeAPI\Editor\Model\Type;

/**
 * Generator which transforms a TypeSchema specification to a document
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Parser
{
    private const DEFINITION_TYPES = [
        Type::TYPE_STRUCT,
        Type::TYPE_MAP,
        Type::TYPE_ARRAY,
    ];

    private const PROPERTY_TYPES = [
        Property::TYPE_OBJECT,
        Property::TYPE_MAP,
        Property::TYPE_ARRAY,
        Property::TYPE_STRING,
        Property::TYPE_INTEGER,
        Property::TYPE_NUMBER,
        Property::TYPE_BOOLEAN,
        Property::TYPE_ANY,
        Property::TYPE_GENERIC,
    ];

    private SchemaManagerInterface $schemaManager;

    public function __construct(SchemaManagerInterface $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * Parses a TypeSchema specification and returns a document
     *
     * @throws ParserException
     */
    public function parse(\stdClass $data): Document
    {
        $baseUrl = null;
        if (isset($data->baseUrl) && is_string($data->baseUrl)) {
            $baseUrl = $data->baseUrl;
        }

        $security = null;
        if (isset($data->security) && $data->security instanceof \stdClass) {
            $security = new Security((array) $data->security);
        }

        $imports = [];
        if (isset($data->import) && $data->import instanceof \stdClass) {
            foreach (get_object_vars($data->import) as $name => $import) {
                if (!is_string($import)) {
                    continue;
                }

                try {
                    $imports[] = $this->parseImport($name, $import);
                } catch (InvalidSchemaException $e) {
                    throw new ParserException('Could not parse import ' . $name . ', got: ' . $e->getMessage(), previous: $e);
                }
            }
        }

        $operations = [];
        if (isset($data->operations) && $data->operations instanceof \stdClass) {
            foreach (get_object_vars($data->operations) as $name => $operation) {
                if (!$operation instanceof \stdClass) {
                    continue;
                }

                $operations[] = $this->parseOperation($name, $operation);
            }
        }

        $rootRef = $this->getString($data, ['root', '$ref']);
        $root = null;

        $types = [];
        $index = 0;
        if (isset($data->definitions) && $data->definitions instanceof \stdClass) {
            foreach (get_object_vars($data->definitions) as $name => $type) {
                if (!$type instanceof \stdClass) {
                    continue;
                }

                if ($rootRef === $name) {
                    $root = $index;
                }
                $types[] = $this->parseDefinitionType($name, $type);
                $index++;
            }
        }

        return new Document($imports, $operations, $types, $root, $baseUrl, $security);
    }

    /**
     * @throws ParserException
     */
    public function parseJson(string $json): Document
    {
        $data = json_decode($json);
        if (!$data instanceof \stdClass) {
            throw new ParserException('Could not parse json file');
        }

        return $this->parse($data);
    }

    /**
     * @throws ParserException
     */
    public function parseFile(string $file): Document
    {
        if (!is_file($file)) {
            throw new ParserException('File does not exist');
        }

        return $this->parseJson((string) file_get_contents($file));
    }

    /**
     * @throws ParserException
     * @throws InvalidSchemaException
     */
    private function parseImport(string $name, string $url): Import
    {
        $return = new Import([]);
        $return->setAlias($name);
        $return->setUrl($url);

        $types = [];
        $schema = $this->schemaManager->getSchema(SchemaSource::fromString($url));
        foreach ($schema->getDefinitions()->getTypes() as $name => $type) {
            $object = new \stdClass();
            foreach ($type->toArray() as $key => $value) {
                $object->{$key} = $value;
            }

            $types[] = $this->parseDefinitionType($name, $object);
        }

        $return->setTypes($types);

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function parseOperation(string $name, \stdClass $operation): Operation
    {
        $return = new Operation([]);
        $return->setName($name);

        if (isset($operation->method) && is_string($operation->method)) {
            $return->setHttpMethod($operation->method);
        }

        if (isset($operation->path) && is_string($operation->path)) {
            $return->setHttpPath($operation->path);
        }

        $payload = null;
        $payloadShape = null;
        if (isset($operation->arguments) && $operation->arguments instanceof \stdClass) {
            $arguments = [];
            foreach (get_object_vars($operation->arguments) as $name => $rawArgument) {
                $shape = null;
                $argument = $this->parseArgument($name, $rawArgument, $shape);
                if ($argument->getIn() === 'body') {
                    $payload = $argument->getType();
                    $payloadShape = $shape;
                } else {
                    $arguments[] = $argument;
                }
            }
            $return->setArguments($arguments);
        }

        if ($payload !== null) {
            $return->setPayload($payload);
            if ($payloadShape !== null) {
                $return->setPayloadShape($payloadShape);
            }
        }

        if (isset($operation->throws) && is_array($operation->throws)) {
            $throws = [];
            foreach ($operation->throws as $throw) {
                $throws[] = $this->parseThrow($throw);
            }
            $return->setThrows($throws);
        }

        if (isset($operation->return) && $operation->return instanceof \stdClass) {
            if (isset($operation->return->code) && is_int($operation->return->code)) {
                $return->setHttpCode($operation->return->code);
            }

            if ($return->getHttpCode() === 204) {
                $return->setReturn(null);
            } else {
                if (isset($operation->return->schema) && $operation->return->schema instanceof \stdClass) {
                    $shape = null;
                    $return->setReturn($this->resolveType($operation->return->schema, $shape));
                    if ($shape !== null) {
                        $return->setReturnShape($shape);
                    }
                } elseif (isset($operation->return->contentType) && is_string($operation->return->contentType)) {
                    $return->setReturn($operation->return->contentType);
                    $return->setReturnShape('mime');
                }
            }
        }

        if (isset($operation->description) && is_string($operation->description)) {
            $return->setDescription($operation->description);
        }

        if (isset($operation->stability) && is_int($operation->stability)) {
            $return->setStability($operation->stability);
        }

        if (isset($operation->security) && is_array($operation->security)) {
            $return->setSecurity($operation->security);
        }

        if (isset($operation->authorization) && is_bool($operation->authorization)) {
            $return->setAuthorization($operation->authorization);
        }

        if (isset($operation->tags) && is_array($operation->tags)) {
            $return->setTags($operation->tags);
        }

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function parseArgument(string $name, \stdClass $argument, ?string &$shape = null): Argument
    {
        $in = $argument->in ?? null;
        if (!is_string($in)) {
            throw new ParserException('Provided argument in must be a string');
        }

        $return = new Argument([]);
        $return->setName($name);
        $return->setIn($in);

        if (isset($argument->schema) && $argument->schema instanceof \stdClass) {
            $return->setType($this->resolveType($argument->schema, $shape));
        } elseif (isset($argument->contentType) && is_string($argument->contentType)) {
            $return->setType($argument->contentType);
            $shape = 'mime';
        } else {
            throw new ParserException('Provided argument schema not available');
        }

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function parseThrow(\stdClass $throw): Error
    {
        $code = $throw->code ?? null;
        if (!is_int($code)) {
            throw new ParserException('Provided throw code must be an integer');
        }

        $return = new Error([]);
        $return->setCode($code);

        if (isset($throw->schema) && $throw->schema instanceof \stdClass) {
            $shape = null;
            $return->setType($this->resolveType($throw->schema, $shape));
            if ($shape !== null) {
                $return->setTypeShape($shape);
            }
        } else {
            throw new ParserException('Provided throw schema not available');
        }

        return $return;
    }

    private function resolveType(\stdClass $schema, ?string &$shape = null): string
    {
        $ref = $this->getString($schema, ['target', '$ref']);
        $type = $this->getString($schema, ['type']);
        $generic = $this->getString($schema, ['$generic', 'name']);

        if (!empty($ref)) {
            return $ref;
        } elseif (!empty($generic)) {
            return $generic;
        } elseif (!empty($type)) {
            $schema = $this->getObject($schema, ['schema', 'items', 'additionalProperties']);
            if ($schema instanceof \stdClass && ($type === 'object' || $type === 'map')) {
                $shape = 'map';
                return $this->resolveType($schema);
            } elseif ($schema instanceof \stdClass && $type === 'array') {
                $shape = 'array';
                return $this->resolveType($schema);
            } else {
                return $type;
            }
        } else {
            throw new ParserException('Provided an invalid schema');
        }
    }

    /**
     * @throws ParserException
     */
    private function parseDefinitionType(string $name, \stdClass $type): Type
    {
        $return = new Type([]);
        $return->setName($name);
        $return->setType($this->resolveDefinitionType($type));

        $description = $this->getString($type, ['description']);
        if ($description !== null) {
            $return->setDescription($type->description);
        }

        $base = $this->getBoolean($type, ['base']);
        if ($base !== null) {
            $return->setBase($base);
        }

        $parent = $this->getString($type, ['parent', '$extends']);
        if (!empty($parent)) {
            $return->setParent($parent);
        }

        $parent = $this->getObject($type, ['parent']);
        $parentString = $this->getString($type, ['parent', '$ref', '$extends', 'extends']);
        if ($parent instanceof \stdClass && !empty($parentString)) {
            $parent = (object) [
                'type' => 'object',
                'target' => $parentString,
            ];
        }

        if ($parent instanceof \stdClass && isset($parent->target) && is_string($parent->target)) {
            $return->setParent($parent->target);

            $template = $this->getObject($parent, ['template', '$template']);
            if ($template instanceof \stdClass) {
                $return->setTemplate(get_object_vars($template));
            }
        }

        $discriminator = $this->getString($type, ['discriminator']);
        if (!empty($discriminator)) {
            $return->setDiscriminator($discriminator);
        }

        $mapping = $this->getObject($type, ['mapping']);
        if ($mapping instanceof \stdClass) {
            $return->setMapping((array) $mapping);
        }

        $properties = $this->getObject($type, ['properties']);
        if ($properties instanceof \stdClass) {
            $props = [];
            foreach (get_object_vars($type->properties) as $name => $property) {
                $props[] = $this->parsePropertyType($name, $property);
            }

            $return->setProperties($props);
        }

        $schema = $this->getObject($type, ['schema', 'additionalProperties', 'items']);
        if ($schema instanceof \stdClass) {
            $return->setReference($this->resolveType($schema));
        }

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function parsePropertyType(string $name, \stdClass $property): Property
    {
        $reference = '';
        $generic = '';

        $return = new Property([]);
        $return->setName($name);
        $return->setType($this->resolvePropertyType($property, $reference, $generic));

        if (!empty($reference)) {
            $return->setReference($reference);
        }

        if (!empty($generic)) {
            $return->setGeneric($generic);
        }

        $template = $this->getObject($property, ['template', '$template']);
        if ($template instanceof \stdClass) {
            $return->setTemplate(get_object_vars($template));
        }

        $description = $this->getString($property, ['description']);
        if ($description !== null) {
            $return->setDescription($description);
        }

        $format = $this->getString($property, ['format']);
        if ($format !== null) {
            $return->setFormat($format);
        }

        $deprecated = $this->getBoolean($property, ['deprecated']);
        if ($deprecated !== null) {
            $return->setDeprecated($deprecated);
        }

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function resolveDefinitionType(\stdClass $type): string
    {
        $typeName = $this->getString($type, ['type']);
        if (empty($typeName)) {
            $parent = $this->getObject($type, ['parent']);
            $extends = $this->getString($type, ['$extends', 'parent']);
            $properties = $this->getObject($type, ['properties']);
            $additionalProperties = $this->getObject($type, ['additionalProperties']);
            $items = $this->getObject($type, ['items']);

            if (!empty($properties) || !empty($extends) || !empty($parent)) {
                $typeName = Type::TYPE_STRUCT;
            } elseif (!empty($additionalProperties)) {
                $typeName = Type::TYPE_MAP;
            } elseif (!empty($items)) {
                $typeName = Type::TYPE_ARRAY;
            }
        }

        if ($typeName === 'object') {
            $typeName = 'struct';
        }

        if (empty($typeName)) {
            throw new ParserException('Could not resolve definition type');
        }

        if (!in_array($typeName, self::DEFINITION_TYPES)) {
            throw new ParserException('Invalid definition type, allowed: ' . implode(', ', self::DEFINITION_TYPES));
        }

        return $typeName;
    }

    /**
     * @throws ParserException
     */
    private function resolvePropertyType(\stdClass $type, string &$reference, string &$generic): string
    {
        $typeName = $this->getString($type, ['type']);
        if (empty($typeName)) {
            $additionalProperties = $this->getObject($type, ['additionalProperties']);
            $items = $this->getObject($type, ['items']);

            if (!empty($additionalProperties)) {
                $typeName = Property::TYPE_MAP;
            } elseif (!empty($items)) {
                $typeName = Property::TYPE_ARRAY;
            }
        }

        if (empty($typeName)) {
            throw new ParserException('Could not resolve property type');
        }

        if ($typeName === 'reference') {
            $typeName = 'object';
        }

        if (in_array($typeName, [Property::TYPE_MAP, Property::TYPE_ARRAY])) {
            $schema = $this->getObject($type, ['schema', 'additionalProperties', 'items']);
            if (!$schema instanceof \stdClass) {
                throw new ParserException('Could not resolve map/array schema type');
            }

            $schemaReference = '';
            $schemaGeneric = '';
            $schemaTypeName = $this->resolvePropertyType($schema, $schemaReference, $schemaGeneric);

            if ($schemaTypeName === Property::TYPE_OBJECT) {
                $reference = $schemaReference;
                $generic = $schemaGeneric;
            } elseif ($schemaTypeName === Property::TYPE_MAP || $schemaTypeName === Property::TYPE_ARRAY) {
                $reference = $schemaTypeName . ':' . $schemaReference;
                $generic = $schemaGeneric;
            } else {
                $reference = $schemaTypeName;
                $generic = $schemaGeneric;
            }
        } else {
            $target = $this->getString($type, ['$ref', 'target']);
            if (!empty($target)) {
                $typeName = Property::TYPE_OBJECT;
                $reference = $target;
            }

            $name = $this->getString($type, ['$generic', 'name']);
            if (!empty($name)) {
                $typeName = Property::TYPE_GENERIC;
                $generic = $name;
            }
        }

        if (!in_array($typeName, self::PROPERTY_TYPES)) {
            throw new ParserException('Invalid property type, allowed: ' . implode(', ', self::PROPERTY_TYPES));
        }

        return $typeName;
    }

    private function getObject(\stdClass $data, array $keywords = []): ?\stdClass
    {
        foreach ($keywords as $keyword) {
            if (isset($data->{$keyword}) && $data->{$keyword} instanceof \stdClass) {
                return $data->{$keyword};
            }
        }

        return null;
    }

    private function getString(\stdClass $data, array $keywords = []): ?string
    {
        foreach ($keywords as $keyword) {
            if (isset($data->{$keyword}) && is_string($data->{$keyword})) {
                return $data->{$keyword};
            }
        }

        return null;
    }

    private function getBoolean(\stdClass $data, array $keywords = []): ?bool
    {
        foreach ($keywords as $keyword) {
            if (isset($data->{$keyword}) && is_bool($data->{$keyword})) {
                return $data->{$keyword};
            }
        }

        return null;
    }
}
