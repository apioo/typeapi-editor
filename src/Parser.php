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

use PSX\Schema\SchemaManagerInterface;
use PSX\Schema\Type as SchemaType;
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
        // @TODO handle import

        $operations = [];
        if (isset($data->operations) && $data->operations instanceof \stdClass) {
            foreach (get_object_vars($data->operations) as $name => $operation) {
                $operations[] = $this->parseOperation($name, $operation);
            }
        }

        $rootRef = $data->{'$ref'} ?? null;
        $root = null;

        $types = [];
        $index = 0;
        if (isset($data->definitions) && $data->definitions instanceof \stdClass) {
            foreach (get_object_vars($data->definitions) as $name => $type) {
                if ($rootRef === $name) {
                    $root = $index;
                }
                $types[] = $this->parseType($name, $type);
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

        return $this->parseJson(file_get_contents($file));
    }

    private function parseImport(string $name, \stdClass $import): Import
    {
        $return = new Import([]);
        $return->setAlias($name);
        //$import->setVersion();
        //$import->setDocument();

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
                    $return->setReturn($this->parseSchema($operation->return->schema, $shape));
                    if ($shape !== null) {
                        $return->setReturnShape($shape);
                    }
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
            $return->setType($this->parseSchema($argument->schema, $shape));
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
            $return->setType($this->parseSchema($throw->schema, $shape));
            if ($shape !== null) {
                $return->setTypeShape($shape);
            }
        } else {
            throw new ParserException('Provided throw schema not available');
        }

        return $return;
    }

    private function parseSchema(\stdClass $schema, ?string &$shape = null): string
    {
        if (isset($schema->{'$ref'}) && is_string($schema->{'$ref'})) {
            return $schema->{'$ref'};
        } elseif (isset($schema->type) && is_string($schema->type)) {
            if ($schema->type === 'object' && isset($schema->additionalProperties) && $schema->additionalProperties instanceof \stdClass) {
                $shape = 'map';
                return $this->parseSchema($schema->additionalProperties);
            } elseif ($schema->type === 'array' && isset($schema->items) && $schema->items instanceof \stdClass) {
                $shape = 'array';
                return $this->parseSchema($schema->items);
            } else {
                return $schema->type;
            }
        } elseif (isset($schema->{'$generic'}) && is_string($schema->{'$generic'})) {
            return 'T';
        } else {
            throw new ParserException('Provided an invalid schema');
        }
    }

    /**
     * @throws ParserException
     */
    private function parseType(string $name, \stdClass $type): Type
    {
        $return = new Type([]);
        $return->setName($name);
        $return->setType($this->detectType($type));

        if (isset($type->description) && is_string($type->description)) {
            $return->setDescription($type->description);
        }

        if (isset($type->{'$extends'}) && is_string($type->{'$extends'})) {
            $return->setParent($type->{'$extends'});
        }

        if (isset($type->{'$ref'}) && is_string($type->{'$ref'})) {
            $return->setRef($type->{'$ref'});
        }

        if (isset($type->{'$template'}) && $type->{'$template'} instanceof \stdClass) {
            foreach (get_object_vars($type->{'$template'}) as $ref) {
                if (is_string($ref)) {
                    $return->setTemplate($ref);
                    break;
                }
            }
        }

        if (isset($type->properties) && $type->properties instanceof \stdClass) {
            $properties = [];
            foreach (get_object_vars($type->properties) as $name => $property) {
                $properties[] = $this->parseProperty($name, $property);
            }
            $return->setProperties($properties);
        }

        if (isset($type->additionalProperties) && $type->additionalProperties instanceof \stdClass) {
            $return->setRef($this->parseSchema($type->additionalProperties));
        }

        if (isset($type->required) && is_array($type->required)) {
            $return->setRequired($type->required);
        }

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function parseProperty(string $name, \stdClass $property): Property
    {
        $refs = [];

        $return = new Property([]);
        $return->setName($name);
        $return->setType($this->detectPropertyType($property, $refs));
        if (count($refs) > 0) {
            $return->setRefs($refs);
        }

        if (isset($property->description) && is_string($property->description)) {
            $return->setDescription($property->description);
        }

        if (isset($property->format) && is_string($property->format)) {
            $return->setFormat($property->format);
        }

        if (isset($property->pattern) && is_string($property->pattern)) {
            $return->setPattern($property->pattern);
        }

        if (isset($property->minLength) && is_int($property->minLength)) {
            $return->setMinLength($property->minLength);
        }

        if (isset($property->maxLength) && is_int($property->maxLength)) {
            $return->setMaxLength($property->maxLength);
        }

        if (isset($property->minimum) && is_int($property->minimum)) {
            $return->setMinimum($property->minimum);
        }

        if (isset($property->maximum) && is_int($property->maximum)) {
            $return->setMaximum($property->maximum);
        }

        if (isset($property->deprecated) && is_bool($property->deprecated)) {
            $return->setDeprecated($property->deprecated);
        }

        if (isset($property->nullable) && is_bool($property->nullable)) {
            $return->setNullable($property->nullable);
        }

        if (isset($property->readonly) && is_bool($property->readonly)) {
            $return->setReadonly($property->readonly);
        }

        return $return;
    }

    /**
     * @throws ParserException
     */
    private function detectType(\stdClass $type): string
    {
        if ((isset($type->properties) && $type->properties instanceof \stdClass) || (isset($type->{'$extends'}) && is_string($type->{'$extends'}))) {
            return Type::TYPE_OBJECT;
        } elseif (isset($type->additionalProperties) && $type->additionalProperties instanceof \stdClass) {
            return Type::TYPE_MAP;
        } elseif (isset($type->{'$ref'}) && is_string($type->{'$ref'})) {
            return Type::TYPE_REFERENCE;
        } else {
            throw new ParserException('Could not determine type');
        }
    }

    private function detectPropertyType(\stdClass $type, array &$refs, int $count = 0): string
    {
        if (isset($type->properties) && $type->properties instanceof \stdClass) {
            return Property::TYPE_OBJECT;
        } elseif (isset($type->additionalProperties) && $type->additionalProperties instanceof \stdClass) {
            $this->detectPropertyType($type->additionalProperties, $refs, $count + 1);
            return Property::TYPE_MAP;
        } elseif (isset($type->items) && $type->items instanceof \stdClass) {
            $this->detectPropertyType($type->items, $refs, $count + 1);
            return Property::TYPE_ARRAY;
        } elseif (isset($type->oneOf) && is_array($type->oneOf)) {
            $refs = array_merge($refs, $this->parseComposite($type->oneOf));
            return Property::TYPE_UNION;
        } elseif (isset($type->allOf) && is_array($type->allOf)) {
            $refs = array_merge($refs, $this->parseComposite($type->allOf));
            return Property::TYPE_INTERSECTION;
        } elseif (isset($type->{'$ref'}) && is_string($type->{'$ref'})) {
            $refs[] = $type->{'$ref'};
            return Property::TYPE_OBJECT;
        } elseif (isset($type->{'$generic'}) && is_string($type->{'$generic'})) {
            $refs[] = $type->{'$generic'};
            return Property::TYPE_GENERIC;
        } elseif (isset($type->type) && is_string($type->type)) {
            if ($count > 0) {
                $refs[] = $type->type;
            }
            $schemaType = SchemaType::tryFrom($type->type);
            if ($schemaType === SchemaType::STRING) {
                return Property::TYPE_STRING;
            } elseif ($schemaType === SchemaType::INTEGER) {
                return Property::TYPE_INTEGER;
            } elseif ($schemaType === SchemaType::NUMBER) {
                return Property::TYPE_NUMBER;
            } elseif ($schemaType === SchemaType::BOOLEAN) {
                return Property::TYPE_BOOLEAN;
            } elseif ($schemaType === SchemaType::ANY) {
                return Property::TYPE_ANY;
            }
        }

        throw new ParserException('Could not determine type');
    }

    private function parseComposite(array $items): array
    {
        $refs = [];
        foreach ($items as $ref) {
            if (!$ref instanceof \stdClass) {
                continue;
            }

            $refs[] = $this->parseSchema($ref);
        }
        return $refs;
    }
}
