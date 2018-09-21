<?php

namespace Abac\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class AbacRole
{
	protected $auth;

	/**
	 * Creates a new instance of the middleware.
	 *
	 * @param Guard $auth
	 */
	public function __construct(Guard $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Closure $next
	 * @param  $roles
	 * @return mixed
	 */
    public function handle($request, Closure $next, $role)
    {
        \Log::info($role);

        if ($this->auth->guest() || !\Abac::hasRole($role)) {
            abort(403);
        }

        return $next($request);
    }
}
