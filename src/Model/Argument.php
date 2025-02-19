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

namespace TypeAPI\Editor\Model;

/**
 * Argument
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Argument implements \JsonSerializable
{
    private ?string $name;
    private ?string $in;
    private ?string $type;

    public function __construct(array $argument)
    {
        $this->name = $argument['name'] ?? null;
        $this->in = $argument['in'] ?? null;
        $this->type = $argument['type'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getIn(): ?string
    {
        return $this->in;
    }

    public function setIn(?string $in): void
    {
        $this->in = $in;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'in' => $this->in,
            'type' => $this->type,
        ], function ($value) {
            return $value !== null;
        });
    }
}
