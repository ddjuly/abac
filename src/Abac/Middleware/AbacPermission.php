<?php

namespace Abac\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class AbacPermission
{
	protected $auth;

	/**
	 */
	public function __construct(Guard $auth)
	{
		$this->auth = $auth;
	}

	/**
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Closure $next
	 * @param  $permissions
	 * @return mixed
	 */
    public function handle($request, Closure $next, $permission)
    {
        \Log::info($permission);

        if ($this->auth->guest() || !\Abac::hasPermission($permission)) {
            abort(404);
        }

        return $next($request);
    }
}
