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
 * Security
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://phpsx.org
 */
class Security implements \JsonSerializable
{
    private ?string $type;
    private ?string $name;
    private ?string $in;
    private ?string $tokenUrl;
    private ?string $authorizationUrl;
    private ?array $scopes;

    public function __construct(array $security)
    {
        $this->type = $security['type'] ?? null;
        $this->name = $security['name'] ?? null;
        $this->in = $security['in'] ?? null;
        $this->tokenUrl = $security['tokenUrl'] ?? null;
        $this->authorizationUrl = $security['authorizationUrl'] ?? null;

        $scopes = $security['scopes'] ?? null;
        if ($scopes instanceof \stdClass) {
            $scopes = array_keys((array) $scopes);
        }

        $this->scopes = $scopes;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
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

    public function getTokenUrl(): ?string
    {
        return $this->tokenUrl;
    }

    public function setTokenUrl(?string $tokenUrl): void
    {
        $this->tokenUrl = $tokenUrl;
    }

    public function getAuthorizationUrl(): ?string
    {
        return $this->authorizationUrl;
    }

    public function setAuthorizationUrl(?string $authorizationUrl): void
    {
        $this->authorizationUrl = $authorizationUrl;
    }

    public function getScopes(): ?array
    {
        return $this->scopes;
    }

    public function setScopes(?array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'name' => $this->name,
            'in' => $this->in,
            'tokenUrl' => $this->tokenUrl,
            'authorizationUrl' => $this->authorizationUrl,
            'scopes' => $this->scopes,
        ], function ($value) {
            return $value !== null;
        });
    }
}
