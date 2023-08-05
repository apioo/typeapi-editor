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

use PSX\Api\Exception\ArgumentNotFoundException;
use PSX\Api\OperationInterface;
use PSX\Api\SpecificationInterface;
use PSX\Schema\DefinitionsInterface;
use PSX\Schema\SchemaManagerInterface;
use PSX\Schema\TypeInterface;
use TypeAPI\Editor\Exception\ParserException;
use TypeAPI\Editor\Model\Argument;
use TypeAPI\Editor\Model\Document;
use TypeAPI\Editor\Model\Error;
use TypeAPI\Editor\Model\Import;
use TypeAPI\Editor\Model\Operation;
use TypeAPI\Editor\Model\Property;
use TypeAPI\Editor\Model\Type;
use PSX\Schema\Type as SchemaType;

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
    public function parse(string $json): Document
    {
        $data = \json_decode($json);
        if (!$data instanceof \stdClass) {
            throw new ParserException('Could not parse json');
        }

        $imports = [];
        // @TODO handle import

        $operations = [];
        if (isset($data->operations) && $data->operations instanceof \stdClass) {
            foreach ($data->operations as $name => $operation) {
                $operations[] = $this->parseOperation($name, $operation);
            }
        }

        $types = [];
        if (isset($data->definitions) && $data->definitions instanceof \stdClass) {
            foreach ($data->definitions as $name => $type) {
                $types[] = $this->parseType($name, $type);
            }
        }

        return new Document($imports, $operations, $types);
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

        if (isset($operation->arguments) && $operation->arguments instanceof \stdClass) {
            $arguments = [];
            foreach ($operation->arguments as $name => $argument) {
                $arguments[] = $this->parseArgument($name, $argument);
            }
            $return->setArguments($arguments);
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

            if (isset($operation->return->schema) && $operation->return->schema instanceof \stdClass) {
                $return->setReturn($this->parseSchema($operation->return->schema));
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

    private function parseArgument(string $name, \stdClass $argument): Argument
    {
        $in = $argument->in ?? null;
        if (!is_string($in)) {
            throw new ParserException('Provided argument in must be a string');
        }

        $return = new Argument([]);
        $return->setName($name);
        $return->setIn($in);

        if (isset($argument->schema) && $argument->schema instanceof \stdClass) {
            $return->setType($this->parseSchema($argument->schema));
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
            $return->setType($this->parseSchema($throw->schema));
        } else {
            throw new ParserException('Provided throw schema not available');
        }

        return $return;
    }

    private function parseSchema(\stdClass $schema): string
    {
        if (isset($schema->{'$ref'}) && is_string($schema->{'$ref'})) {
            return $schema->{'$ref'};
        } elseif (isset($schema->type) && is_string($schema->type)) {
            return $schema->type;
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
            foreach ($type->{'$template'} as $ref) {
                if (is_string($ref)) {
                    $return->setTemplate($ref);
                    break;
                }
            }
        }

        if (isset($type->properties) && $type->properties instanceof \stdClass) {
            $properties = [];
            foreach ($type->properties as $name => $property) {
                $properties[] = $this->parseProperty($name, $property);
            }
            $return->setProperties($properties);
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

        if (isset($property->type) && is_string($property->type)) {
            $return->setType($property->type);
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
        if (isset($type->properties) && $type->properties instanceof \stdClass) {
            return Type::TYPE_OBJECT;
        } elseif (isset($type->additionalProperties) && $type->additionalProperties instanceof \stdClass) {
            return Type::TYPE_MAP;
        } elseif (isset($type->{'$ref'}) && is_string($type->{'$ref'})) {
            return Type::TYPE_REFERENCE;
        } else {
            throw new ParserException('Could not determine type');
        }
    }

    private function detectPropertyType(\stdClass $type, array &$refs): string
    {
        if (isset($type->properties) && $type->properties instanceof \stdClass) {
            return Property::TYPE_OBJECT;
        } elseif (isset($type->additionalProperties) && $type->additionalProperties instanceof \stdClass) {
            $refs[] = $this->parseSchema($type->additionalProperties);
            return Property::TYPE_MAP;
        } elseif (isset($type->items) && $type->items instanceof \stdClass) {
            $refs[] = $this->parseSchema($type->items);
            return Property::TYPE_ARRAY;
        } elseif (isset($type->oneOf) && is_array($type->oneOf)) {
            foreach ($type->oneOf as $ref) {
                if (!$ref instanceof \stdClass) {
                    continue;
                }
                $refs[] = $this->parseSchema($ref);
            }
            return Property::TYPE_UNION;
        } elseif (isset($type->allOf) && is_array($type->allOf)) {
            foreach ($type->allOf as $ref) {
                if (!$ref instanceof \stdClass) {
                    continue;
                }
                $refs[] = $this->parseSchema($ref);
            }
            return Property::TYPE_INTERSECTION;
        } elseif (isset($type->{'$ref'}) && is_string($type->{'$ref'})) {
            $refs[] = $type->{'$ref'};
            return Property::TYPE_OBJECT;
        } elseif (isset($type->type) && is_string($type->type)) {
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
}
