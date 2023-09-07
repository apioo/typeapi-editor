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
 * Property
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Property implements \JsonSerializable
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_OBJECT = 'object';
    public const TYPE_MAP = 'map';
    public const TYPE_ARRAY = 'array';
    public const TYPE_UNION = 'union';
    public const TYPE_INTERSECTION = 'intersection';
    public const TYPE_GENERIC = 'T';
    public const TYPE_ANY = 'any';

    private ?string $name;
    private ?string $description;
    private ?string $type;
    private ?string $format;
    private ?string $pattern;
    private ?int $minLength;
    private ?int $maxLength;
    private ?int $minimum;
    private ?int $maximum;
    private ?bool $deprecated;
    private ?bool $nullable;
    private ?bool $readonly;
    private ?array $refs;

    public function __construct(array $property)
    {
        $this->name = $property['name'] ?? null;
        $this->description = $property['description'] ?? null;
        $this->type = $property['type'] ?? null;
        $this->format = $property['format'] ?? null;
        $this->pattern = $property['pattern'] ?? null;
        $this->minLength = $property['minLength'] ?? null;
        $this->maxLength = $property['maxLength'] ?? null;
        $this->minimum = $property['minimum'] ?? null;
        $this->maximum = $property['maximum'] ?? null;
        $this->deprecated = $property['deprecated'] ?? null;
        $this->nullable = $property['nullable'] ?? null;
        $this->readonly = $property['readonly'] ?? null;
        $this->refs = $property['refs'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    public function setPattern(?string $pattern): void
    {
        $this->pattern = $pattern;
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function setMinLength(?int $minLength): void
    {
        $this->minLength = $minLength;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function setMaxLength(?int $maxLength): void
    {
        $this->maxLength = $maxLength;
    }

    public function getMinimum(): ?int
    {
        return $this->minimum;
    }

    public function setMinimum(?int $minimum): void
    {
        $this->minimum = $minimum;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    public function setMaximum(?int $maximum): void
    {
        $this->maximum = $maximum;
    }

    public function getDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function setDeprecated(?bool $deprecated): void
    {
        $this->deprecated = $deprecated;
    }

    public function getNullable(): ?bool
    {
        return $this->nullable;
    }

    public function setNullable(?bool $nullable): void
    {
        $this->nullable = $nullable;
    }

    public function getReadonly(): ?bool
    {
        return $this->readonly;
    }

    public function setReadonly(?bool $readonly): void
    {
        $this->readonly = $readonly;
    }

    public function getRefs(): ?array
    {
        return $this->refs;
    }

    public function setRefs(?array $refs): void
    {
        $this->refs = $refs;
    }

    public function getFirstRef(): ?string
    {
        return !empty($this->refs) ? reset($this->refs) : null;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'format' => $this->format,
            'pattern' => $this->pattern,
            'minLength' => $this->minLength,
            'maxLength' => $this->maxLength,
            'minimum' => $this->minimum,
            'maximum' => $this->maximum,
            'deprecated' => $this->deprecated,
            'nullable' => $this->nullable,
            'readonly' => $this->readonly,
            'refs' => $this->refs,
        ], function ($value) {
            return $value !== null;
        });
    }
}
