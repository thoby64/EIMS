<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditHttpRequest
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            if ($request->user() && $request->route()?->getName()) {
                $this->audit->write('request', $this->action($request), [
                    'outcome' => 'failure',
                    'http_status' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500,
                    'context' => ['input' => $this->audit->redactedInput($request), 'exception' => $exception::class],
                ], $request);
            }
            throw $exception;
        }
        if ($request->user() && $request->route()?->getName() && ! in_array($request->route()->getName(), ['login', 'login.store', 'logout'], true)) {
            $this->audit->write('request', $this->action($request), ['outcome' => $response->getStatusCode() < 400 ? 'success' : 'failure', 'http_status' => $response->getStatusCode(), 'context' => ['input' => $this->audit->redactedInput($request)]], $request);
        }

        return $response;
    }

    private function action(Request $r): string
    {
        $name = (string) $r->route()?->getName();

        return match (true) {
            str_ends_with($name, '.export') => 'exported',in_array($r->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) => 'submitted',default => 'viewed'
        };
    }
}
