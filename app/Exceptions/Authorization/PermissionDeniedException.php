<?php

namespace App\Exceptions\Authorization;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class PermissionDeniedException extends BaseApiException
{
    protected string $resource;
    protected string $action;
    protected ?string $permission;

    public function __construct(string $resource, string $action, ?string $permission = null)
    {
        $this->resource = $resource;
        $this->action = $action;
        $this->permission = $permission;

        $message = __("error.permission.{$resource}.{$action}");

        if ($message === "error.permission.{$resource}.{$action}") {
            $message = __('error.permission.generic', ['resource' => $resource, 'action' => $action]);
        }

        parent::__construct($message, 403, ErrorCode::ACCESS_DENIED->value);
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }
}
