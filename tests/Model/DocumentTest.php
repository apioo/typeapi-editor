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

namespace TypeAPI\Editor\Tests\Model;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use TypeAPI\Editor\Model\Document;
use TypeAPI\Editor\Model\Operation;
use TypeAPI\Editor\Model\Type;

/**
 * DocumentTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class DocumentTest extends TestCase
{
    public function testDocumentJson()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/../resource/document.json'));
        $document = Document::from($json);

        $this->assertDocument($document);
    }

    public function testDocumentYaml()
    {
        $yaml     = Yaml::parse(file_get_contents(__DIR__ . '/../resource/document.json'));
        $document = Document::from($yaml);

        $this->assertDocument($document);
    }

    protected function assertDocument(Document $document): void
    {
        $this->assertEquals(2, count($document->getOperations()));

        $operation = $document->getOperation($document->indexOfOperation('test.execute'));
        $this->assertInstanceOf(Operation::class, $operation);
        $this->assertEquals('test.execute', $operation->getName());
        $this->assertEquals('Executes a test operation', $operation->getDescription());
        $this->assertEquals(2, count($operation->getArguments()));
        $this->assertEquals(1, count($operation->getThrows()));

        $this->assertEquals(8, count($document->getTypes()));

        $type = $document->getType($document->indexOfType('Product'));
        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals('Product', $type->getName());
        $this->assertEquals('struct', $type->getType());
        $this->assertEquals(3, count($type->getProperties()));
    }
}
