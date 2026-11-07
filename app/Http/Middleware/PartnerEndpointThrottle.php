<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
/** Each operation has its own counter. A poll cannot exhaust OTP/payment/form limits. */
class PartnerEndpointThrottle extends ThrottleRequests {
 protected function resolveRequestSignature($request) {
  $route=$request->route();$uri=is_object($route)?$route->uri():$request->path();
  return hash('sha256',parent::resolveRequestSignature($request).'|'.$request->method().'|'.$uri);
 }
 public function handle($request,Closure $next,$maxAttempts=60,$decayMinutes=1,$prefix='') {
  $scoped='partner-endpoint-v2:'.$maxAttempts.':'.$decayMinutes.':'.$prefix;
  try {return parent::handle($request,$next,$maxAttempts,$decayMinutes,$scoped);}
  catch(ThrottleRequestsException $e){
   $retry=max(1,(int)($e->getHeaders()['Retry-After']??60));
   return response()->json(['status'=>false,'message'=>'Please wait '.$retry.' seconds before trying this action again.','retry_after'=>$retry],429,$e->getHeaders());
  }
 }
}
