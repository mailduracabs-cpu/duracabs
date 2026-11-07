<?php
namespace Tests\Unit;
use App\Services\Identity\AadhaarIdentityParser;
use App\Services\Identity\IdentityProviderException;
use PHPUnit\Framework\TestCase;

final class AadhaarIdentityParserTest extends TestCase
{
    private function xml(string $photo = ''): string
    {
        $photo = $photo ?: base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6WQAAAAASUVORK5CYII='));
        return '<Certificate><CertificateData><KycRes><UidData uid="123456781234"><Poi name="Test Partner" dob="01-01-1990"/><Poa house="42" street="Test Road" vtc="Agra" state="Uttar Pradesh" pc="282001"/><Pht>'.$photo.'</Pht></UidData></KycRes></CertificateData></Certificate>';
    }
    public function test_extracts_only_required_details_and_masks_number(): void
    {
        $data=(new AadhaarIdentityParser())->parse($this->xml());
        $this->assertSame('Test Partner',$data['name']);
        $this->assertSame('XXXX XXXX 1234',$data['masked_aadhaar']);
        $this->assertStringContainsString('Agra',$data['address']);
        $this->assertStringStartsWith('data:image/png;base64,',$data['photo']);
        $this->assertStringNotContainsString('123456781234',json_encode($data));
        $this->assertArrayNotHasKey('dob',$data);
    }
    public function test_accepts_offline_xml_shape_from_issued_provider_document(): void
    {
        $xml=str_replace(['<Certificate><CertificateData><KycRes>','</KycRes></CertificateData></Certificate>'],['<OfflinePaperlessKyc>','</OfflinePaperlessKyc>'],$this->xml());
        $this->assertSame('Test Partner',(new AadhaarIdentityParser())->parse($xml)['name']);
    }
    public function test_rejects_external_entities(): void
    {
        $this->expectException(IdentityProviderException::class);
        (new AadhaarIdentityParser())->parse('<!DOCTYPE x [<!ENTITY file SYSTEM "file:///etc/passwd">]>'.$this->xml());
    }
    public function test_rejects_invalid_photo(): void
    {
        $this->expectException(IdentityProviderException::class);
        (new AadhaarIdentityParser())->parse($this->xml(base64_encode('<script>bad</script>')));
    }
    public function test_rejects_missing_name(): void
    {
        $this->expectException(IdentityProviderException::class);
        (new AadhaarIdentityParser())->parse(str_replace('name="Test Partner"','name=""',$this->xml()));
    }
    public function test_rejects_multiple_identity_records(): void
    {
        $this->expectException(IdentityProviderException::class);
        (new AadhaarIdentityParser())->parse('<root>'.$this->xml().$this->xml().'</root>');
    }
    public function test_rejects_oversize_document(): void
    {
        $this->expectException(IdentityProviderException::class);
        (new AadhaarIdentityParser())->parse(str_repeat(' ',2*1024*1024+1));
    }
}
