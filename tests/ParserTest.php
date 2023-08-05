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
use PSX\Api\Parser\TypeAPI;
use PSX\Schema\Document\Document;
use PSX\Schema\Document\Generator;
use PSX\Schema\Document\Operation;
use PSX\Schema\Document\Type;
use PSX\Schema\Parser\TypeSchema;
use PSX\Schema\SchemaManager;
use TypeAPI\Editor\Parser;
use Symfony\Component\Yaml\Yaml;

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
        $spec = (new TypeAPI(new SchemaManager()))->parse(file_get_contents(__DIR__ . '/resource/typeapi.json'));

        $actual = (new Parser(new SchemaManager()))->parse($spec);
        $expect = Document::from(\json_decode(file_get_contents(__DIR__ . '/resource/document.json')));

        $this->assertEquals($expect, $actual);
    }
}
