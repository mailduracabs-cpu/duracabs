<?php

declare(strict_types=1);

namespace App\SEO\AI;

use Illuminate\Http\Client\ConnectionException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class GeminiProvider implements AiProviderInterface
{
    /**
     * Gemini se text generate karta hai.
     *
     * @param array<string, mixed> $options
     */
    public function generate(
        string $prompt,
        array $options = [],
    ): string {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new RuntimeException(
                'Gemini prompt empty nahi ho sakta.',
            );
        }

        $config = $this->getConfig();

        $apiKey = trim(
            (string) ($config['api_key'] ?? ''),
        );

        if ($apiKey === '') {
            throw new RuntimeException(
                'GEMINI_API_KEY configured nahi hai.',
            );
        }

        $model = trim(
            (string) (
                $options['model']
                ?? $config['model']
                ?? 'gemini-2.5-flash'
            ),
        );

        if ($model === '') {
            throw new RuntimeException(
                'Gemini model configured nahi hai.',
            );
        }

        $endpoint = rtrim(
            (string) (
                $config['endpoint']
                ?? 'https://generativelanguage.googleapis.com/v1beta/models'
            ),
            '/',
        );

        $url = sprintf(
            '%s/%s:generateContent',
            $endpoint,
            rawurlencode($model),
        );

        $generationConfig = $this->buildGenerationConfig(
            config: $config,
            options: $options,
        );

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ];

        $systemInstruction = trim(
            (string) ($options['system_instruction'] ?? ''),
        );

        if ($systemInstruction !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    [
                        'text' => $systemInstruction,
                    ],
                ],
            ];
        }

        // One request at a time for this endpoint/key/model. Do not sleep or
        // blindly resend 429 inside the HTTP call; the intake queue owns retries.
        $timeout=max(1,(int)($options['timeout']??$config['timeout']??120));
        $connectTimeout=max(1,(int)($options['connect_timeout']??$config['connect_timeout']??15));
        $quotaKey=$this->quotaKey($endpoint,$model,$apiKey);
        $this->assertQuotaAvailable($quotaKey);
        $lock=Cache::lock($quotaKey.':request',$timeout+$connectTimeout+10);
        if(!$lock->get())throw new GeminiProviderException('provider_busy',0,3);
        try {
            $this->assertQuotaAvailable($quotaKey);
            try {
                $response=Http::acceptJson()->asJson()
                    ->withHeaders(['x-goog-api-key'=>$apiKey])
                    ->connectTimeout($connectTimeout)->timeout($timeout)
                    ->post($url,$payload);
            } catch(ConnectionException) {
                // No previous exception: transport messages can include request URLs.
                throw new GeminiProviderException('connection_failure',0,30);
            } catch(Throwable) {
                throw new GeminiProviderException('request_failure',0,30);
            }
            if($response->status()===429){
                $scope=$this->quotaScope($response->json());
                $previous=Cache::get($quotaKey,[]);
                $failures=min(6,max(0,(int)(is_array($previous)?($previous['failures']??0):0))+1);
                $base=$scope==='daily'?900:60;
                $ceiling=$scope==='daily'?3600:900;
                $backoff=min($ceiling,$base*(2**($failures-1)));
                $seconds=min(86400,max($backoff,$this->serverRetryDelay($response->header('Retry-After'),$response->json()))+random_int(0,5));
                Cache::put($quotaKey,['until'=>$this->clock()+$seconds,'scope'=>$scope,'failures'=>$failures],max(86400,$seconds+3600));
                throw new GeminiProviderException('quota_exceeded',429,$seconds,$scope);
            }
            if($response->failed()){
                $status=$response->status();
                $reason=match(true){
                    in_array($status,[401,403],true)=>'credentials_or_permissions',
                    $status===404=>'model_or_endpoint',
                    $status>=500=>'provider_unavailable',
                    $status>=400&&$status<500=>'invalid_request',
                    default=>'provider_http_error',
                };
                throw new GeminiProviderException($reason,$status,$status>=500?30:0);
            }
            Cache::forget($quotaKey);
        } finally {
            $lock->release();
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException(
                'Gemini API ne invalid response return kiya.',
            );
        }

        $text = $this->extractGeneratedText($data);

        if ($text === '') {
            $finishReason = Arr::get(
                $data,
                'candidates.0.finishReason',
                'unknown',
            );

            $blockReason = Arr::get(
                $data,
                'promptFeedback.blockReason',
            );

            $message = 'Gemini se generated text nahi mila.';

            if (is_scalar($finishReason)) {
                $message .= ' Finish reason: '
                    . (string) $finishReason
                    . '.';
            }

            if (is_scalar($blockReason)) {
                $message .= ' Block reason: '
                    . (string) $blockReason
                    . '.';
            }

            throw new RuntimeException($message);
        }

        return $text;
    }

    /**
     * Provider configured hai ya nahi.
     */
    public function isConfigured(): bool
    {
        return trim(
            (string) config(
                'seo-ai.providers.gemini.api_key',
                '',
            ),
        ) !== '';
    }

    /**
     * Provider identifier.
     */
    public function name(): string
    {
        return 'gemini';
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        $config = config(
            'seo-ai.providers.gemini',
            [],
        );

        if (! is_array($config)) {
            throw new RuntimeException(
                'Gemini provider configuration invalid hai.',
            );
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildGenerationConfig(
        array $config,
        array $options,
    ): array {
        $generationConfig = [
            'maxOutputTokens' => max(
                100,
                (int) (
                    $options['max_output_tokens']
                    ?? $config['max_output_tokens']
                    ?? 4000
                ),
            ),
        ];

        $temperature = $options['temperature']
            ?? $config['temperature']
            ?? null;

        if (
            $temperature !== null
            && $temperature !== ''
            && is_numeric($temperature)
        ) {
            $generationConfig['temperature'] = max(
                0,
                min(2, (float) $temperature),
            );
        }

        $topP = $options['top_p'] ?? null;

        if (
            $topP !== null
            && $topP !== ''
            && is_numeric($topP)
        ) {
            $generationConfig['topP'] = max(
                0,
                min(1, (float) $topP),
            );
        }

        $topK = $options['top_k'] ?? null;

        if (
            $topK !== null
            && $topK !== ''
            && is_numeric($topK)
        ) {
            $generationConfig['topK'] = max(
                1,
                (int) $topK,
            );
        }

        if (
            (bool) ($options['json'] ?? false)
            || ($options['response_format'] ?? null) === 'json'
        ) {
            $generationConfig['responseMimeType'] =
                'application/json';
        }

        return $generationConfig;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractGeneratedText(array $data): string
    {
        $parts = Arr::get(
            $data,
            'candidates.0.content.parts',
            [],
        );

        if (! is_array($parts)) {
            return '';
        }

        $texts = [];

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $text = trim(
                (string) ($part['text'] ?? ''),
            );

            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return trim(
            implode("\n", $texts),
        );
    }

    private function clock(): int
    {
        return CarbonImmutable::now('UTC')->getTimestamp();
    }

    private function quotaKey(string $endpoint,string $model,string $apiKey): string
    {
        return 'gemini:quota:v1:'.hash('sha256',$endpoint.'|'.$model.'|'.$apiKey);
    }

    private function assertQuotaAvailable(string $key): void
    {
        $state=Cache::get($key,[]);
        if(!is_array($state))return;
        $remaining=(int)($state['until']??0)-$this->clock();
        if($remaining>0)throw new GeminiProviderException('quota_cooldown',429,$remaining,(string)($state['scope']??'unknown'));
    }

    private function quotaScope(mixed $response): string
    {
        $details=is_array($response)?Arr::get($response,'error.details',[]):[];
        if(!is_array($details))return 'unknown';
        $minute=false;
        foreach($details as $detail){
            if(!is_array($detail)||!is_array($detail['violations']??null))continue;
            foreach($detail['violations'] as $violation){
                if(!is_array($violation))continue;
                $parts=[];
                foreach(['quotaId','quotaMetric'] as $key)if(is_string($violation[$key]??null))$parts[]=$violation[$key];
                $identity=implode(' ',$parts);
                if(preg_match('/per[ _-]*day|daily/i',$identity))return 'daily';
                if(preg_match('/per[ _-]*minute/i',$identity))$minute=true;
            }
        }
        return $minute?'minute':'unknown';
    }

    private function serverRetryDelay(?string $header,mixed $response): int
    {
        $delay=0;
        if(is_string($header)&&preg_match('/^\d+$/',trim($header)))$delay=(int)trim($header);
        elseif(is_string($header)&&trim($header)!==''){
            $date=strtotime($header);if($date!==false)$delay=max(0,$date-$this->clock());
        }
        $details=is_array($response)?Arr::get($response,'error.details',[]):[];
        foreach(is_array($details)?$details:[] as $detail){
            if(!is_array($detail)||($detail['@type']??'')!=='type.googleapis.com/google.rpc.RetryInfo')continue;
            $value=$detail['retryDelay']??null;
            if(is_string($value)&&preg_match('/^(\d+(?:\.\d+)?)s$/',$value,$m))$delay=max($delay,(int)ceil((float)$m[1]));
        }
        return min(86400,max(0,$delay));
    }
}
