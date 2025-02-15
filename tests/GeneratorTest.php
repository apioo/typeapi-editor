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

namespace TypeAPI\Editor\Tests;

use PHPUnit\Framework\TestCase;
use TypeAPI\Editor\Generator;
use TypeAPI\Editor\Model\Document;

/**
 * GeneratorTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class GeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/resource/document.json'));
        $document = Document::from($json);

        $actual = (new Generator())->generate($document);
        $expect = file_get_contents(__DIR__ . '/resource/typeapi.json');

        $this->assertJsonStringEqualsJsonString($expect, $actual);
    }

    public function testGenerateComplex()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/resource/document_complex.json'));
        $document = Document::from($json);

        $actual = (new Generator())->generate($document);
        $expect = file_get_contents(__DIR__ . '/resource/typeapi_complex.json');

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testGenerateDiscord()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/resource/document_discord.json'));
        $document = Document::from($json);

        $actual = (new Generator())->generate($document);
        $expect = file_get_contents(__DIR__ . '/resource/typeapi_discord.json');

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testGenerateNestedArray()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/resource/document_nested_array.json'));
        $document = Document::from($json);

        $actual = (new Generator())->generate($document);
        $expect = file_get_contents(__DIR__ . '/resource/typeapi_nested_array.json');

        $this->assertJsonStringEqualsJsonString($expect, $actual);
    }

    public function testGenerateQueryObject()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/resource/document_query_object.json'));
        $document = Document::from($json);

        $actual = (new Generator())->generate($document);
        $expect = file_get_contents(__DIR__ . '/resource/typeapi_query_object.json');

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testGenerateSecurity()
    {
        $json     = \json_decode(file_get_contents(__DIR__ . '/resource/document_security.json'));
        $document = Document::from($json);

        $actual = (new Generator())->generate($document);
        $expect = file_get_contents(__DIR__ . '/resource/typeapi_security.json');

        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }
}
