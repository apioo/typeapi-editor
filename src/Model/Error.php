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
 * Error
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Error implements \JsonSerializable
{
    private ?int $code;
    private ?string $type;
    private ?string $typeShape;

    public function __construct(array $throw)
    {
        $this->code = $throw['code'] ?? null;
        $this->type = $throw['type'] ?? null;
        $this->typeShape = $throw['typeShape'] ?? null;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): void
    {
        $this->code = $code;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getTypeShape(): ?string
    {
        return $this->typeShape;
    }

    public function setTypeShape(?string $typeShape): void
    {
        $this->typeShape = $typeShape;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'code' => $this->code,
            'type' => $this->type,
            'typeShape' => $this->typeShape,
        ], function ($value) {
            return $value !== null;
        });
    }
}
