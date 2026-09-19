<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\HttpException;
use App\Core\Request;

final class RoleMiddleware
{
    /** @var list<string> */
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request, Application $app): void
    {
        if ($this->roles === []) {
            return;
        }
        if (!$app->auth()->hasRole(...$this->roles)) {
            throw new HttpException(403, 'K této sekci nemáte oprávnění.');
        }
    }
}
