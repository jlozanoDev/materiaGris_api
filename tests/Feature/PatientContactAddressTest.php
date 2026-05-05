<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Patient;

class PatientContactAddressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_and_retrieve_contact_and_address_fields()
    {
        $data = [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.test',
            'phone' => '+34123456789',
            'mobile' => '+34111222333',
            'contact_name' => 'María Pérez',
            'contact_phone' => '+34999888777',
            'address_line1' => 'Calle Falsa 123',
            'address_line2' => 'Piso 4',
            'neighborhood' => 'Centro',
            'postal_code' => '28001',
            'state' => 'Madrid',
            'country' => 'ES',
        ];


        Patient::create($data);

        $found = Patient::where('email', $data['email'])->first();

        $this->assertNotNull($found);
        $this->assertSame('juan@example.test', $found->email);
        $this->assertSame('+34123456789', $found->phone);
        $this->assertSame('+34111222333', $found->mobile);
        $this->assertSame('María Pérez', $found->contact_name);
        $this->assertSame('+34999888777', $found->contact_phone);
        $this->assertSame('Calle Falsa 123', $found->address_line1);
        $this->assertSame('Piso 4', $found->address_line2);
        $this->assertSame('Centro', $found->neighborhood);
        $this->assertSame('28001', $found->postal_code);
        $this->assertSame('Madrid', $found->state);
        $this->assertSame('ES', $found->country);
    }
}
