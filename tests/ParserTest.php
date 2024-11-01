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

namespace TypeAPI\Editor\Tests;

use PHPUnit\Framework\TestCase;
use PSX\Schema\SchemaManager;
use TypeAPI\Editor\Parser;

/**
 * ParserTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class ParserTest extends TestCase
{
    public function testParse()
    {
        $actual = (new Parser(new SchemaManager()))->parseFile(__DIR__ . '/resource/typeapi.json');
        $expect = file_get_contents(__DIR__ . '/resource/document.json');

        $this->assertJsonStringEqualsJsonString($expect, \json_encode($actual));
    }

    public function testParseComplex()
    {
        $actual = (new Parser(new SchemaManager()))->parseFile(__DIR__ . '/resource/typeapi_complex.json');
        $expect = file_get_contents(__DIR__ . '/resource/document_complex.json');

        $this->assertJsonStringEqualsJsonString($expect, \json_encode($actual));
    }

    public function testParseDiscord()
    {
        $actual = (new Parser(new SchemaManager()))->parseFile(__DIR__ . '/resource/typeapi_discord.json');
        $expect = file_get_contents(__DIR__ . '/resource/document_discord.json');

        $this->assertJsonStringEqualsJsonString($expect, \json_encode($actual));
    }

    public function testParseQueryObject()
    {
        $actual = (new Parser(new SchemaManager()))->parseFile(__DIR__ . '/resource/typeapi_query_object.json');
        $expect = file_get_contents(__DIR__ . '/resource/document_query_object.json');

        $this->assertJsonStringEqualsJsonString($expect, \json_encode($actual));
    }

    public function testParseSecurity()
    {
        $actual = (new Parser(new SchemaManager()))->parseFile(__DIR__ . '/resource/typeapi_security.json');
        $expect = file_get_contents(__DIR__ . '/resource/document_security.json');

        $this->assertJsonStringEqualsJsonString($expect, \json_encode($actual));
    }
}
