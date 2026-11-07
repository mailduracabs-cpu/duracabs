<?php

declare(strict_types=1);
namespace App\SEO\AI;

/** Safe metadata only: never contains a prompt, key, URL or raw provider body. */
final class GeminiProviderException extends \RuntimeException
{
    public readonly string $reason;
    public readonly int $httpStatus;
    public readonly int $retryAfterSeconds;
    public readonly string $quotaScope;

    public function __construct(string $reason, int $httpStatus = 0, int $retryAfterSeconds = 0, string $quotaScope = 'unknown')
    {
        $allowed=['quota_exceeded','quota_cooldown','provider_busy','connection_failure','request_failure','credentials_or_permissions','model_or_endpoint','invalid_request','provider_unavailable','provider_http_error'];
        $this->reason=in_array($reason,$allowed,true)?$reason:'request_failure';
        $this->httpStatus=$httpStatus>=0&&$httpStatus<=599?$httpStatus:0;
        $this->retryAfterSeconds=max(0,min(86400,$retryAfterSeconds));
        $this->quotaScope=in_array($quotaScope,['daily','minute','unknown'],true)?$quotaScope:'unknown';
        $message='Gemini request failed; reason='.$this->reason.'; http_status='.$this->httpStatus.'; retry_after_seconds='.$this->retryAfterSeconds;
        if($this->httpStatus===429)$message.='; quota_scope='.$this->quotaScope;
        parent::__construct($message,$this->httpStatus);
    }
}
