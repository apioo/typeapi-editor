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

namespace TypeAPI\Editor\Model;

/**
 * Import
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Import implements \JsonSerializable
{
    private ?string $alias;
    private ?string $url;
    private ?array $types;

    public function __construct(array $import)
    {
        $this->alias = $import['alias'] ?? null;
        $this->url = $import['url'] ?? null;
        $this->types = $this->convertTypes($import['types'] ?? []);
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getTypes(): ?array
    {
        return $this->types;
    }

    public function setTypes(?array $types): void
    {
        $this->types = $types;
    }

    public function jsonSerialize(): array
    {
        return [
            'alias' => $this->alias,
            'url' => $this->url,
            'types' => $this->types,
        ];
    }

    private function convertTypes(array $types): array
    {
        $result = [];
        foreach ($types as $type) {
            if ($type instanceof \stdClass) {
                $result[] = new Type((array) $type);
            } elseif (is_array($type)) {
                $result[] = new Type($type);
            } elseif ($type instanceof Type) {
                $result[] = $type;
            }
        }

        return $result;
    }
}
