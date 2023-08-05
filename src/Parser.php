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

use PSX\Api\OperationInterface;
use PSX\Api\SpecificationInterface;
use PSX\Schema\DefinitionsInterface;
use PSX\Schema\SchemaManagerInterface;
use PSX\Schema\TypeInterface;
use TypeAPI\Editor\Exception\ParserException;
use TypeAPI\Editor\Model\Document;
use TypeAPI\Editor\Model\Import;
use TypeAPI\Editor\Model\Operation;
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
    public function parse(string $json): Document
    {
        $data = \json_decode($json);
        if (!$data instanceof \stdClass) {
            throw new ParserException('Could not parse json');
        }

        $imports = [];
        // @TODO handle import

        $operations = [];
        // @TODO handle operations

        $types = [];
        // @TODO handle types

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

    private function parseOperation(string $name, \stdClass $operation): Operation
    {
        $return = new Operation([]);
        $return->setName($name);

        return $return;
    }

    private function parseType(string $name, \stdClass $type): Type
    {
        $return = new Type([]);
        $return->setName($name);

        return $return;
    }
}
