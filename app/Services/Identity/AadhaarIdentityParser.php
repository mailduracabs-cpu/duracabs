<?php
declare(strict_types=1);
namespace App\Services\Identity;

/** Parse only XML retrieved server-side from a consented UIDAI-issued document.
 * This is NOT a standalone XML signature verifier and must never accept uploads.
 */
final class AadhaarIdentityParser
{
    public function parse(string $xml): array
    {
        if (strlen($xml) > 2 * 1024 * 1024 || preg_match('/<!\s*(?:DOCTYPE|ENTITY)/i', $xml)) {
            throw new IdentityProviderException('The identity document could not be processed.', 502);
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = new \DOMDocument();
            if (!$doc->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                throw new IdentityProviderException('The identity document could not be processed.', 502);
            }
            $xpath = new \DOMXPath($doc);
            $nodes = $xpath->query('//*[local-name()="UidData"]');
            if ($nodes === false || $nodes->length !== 1) {
                throw new IdentityProviderException('A complete Aadhaar document was not returned.', 422);
            }
            $uid = $nodes->item(0);
            $poi = $xpath->query('./*[local-name()="Poi"]', $uid)?->item(0);
            $poa = $xpath->query('./*[local-name()="Poa"]', $uid)?->item(0);
            $photo = $xpath->query('./*[local-name()="Pht" or local-name()="Photo"]', $uid)?->item(0);
            if (!$poi instanceof \DOMElement || !$poa instanceof \DOMElement || !$photo) {
                throw new IdentityProviderException('Name, photograph or address is unavailable. Please retry or contact support.', 422);
            }
            $name = trim($poi->getAttribute('name'));
            $address = [];
            foreach (['co','house','street','lm','loc','vtc','subdist','dist','state','pc','po','country'] as $key) {
                $value = trim($poa->getAttribute($key));
                if ($value !== '') $address[$key] = $value;
            }
            $photoBytes = base64_decode(preg_replace('/\s+/', '', $photo->textContent), true);
            $image = is_string($photoBytes) && strlen($photoBytes) <= 512 * 1024 ? @getimagesizefromstring($photoBytes) : false;
            if ($name === '' || $address === [] || !$image || !in_array($image['mime'] ?? '', ['image/jpeg','image/png'], true)) {
                throw new IdentityProviderException('The returned identity details are incomplete.', 422);
            }
            // Retain no full Aadhaar number, raw document, DOB, or contact hashes.
            $number = $uid instanceof \DOMElement ? $uid->getAttribute('uid') : '';
            $last4 = preg_match('/([0-9]{4})$/', $number, $match) ? $match[1] : null;
            return [
                'name' => $name,
                'address' => implode(', ', array_values(array_unique($address))),
                'photo' => 'data:'.$image['mime'].';base64,'.base64_encode($photoBytes),
                'masked_aadhaar' => $last4 ? 'XXXX XXXX '.$last4 : null,
            ];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
