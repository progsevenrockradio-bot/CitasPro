<?php

namespace Tests\Feature;

use App\Models\ConsentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_log_user_consent()
    {
        $hash = hash('sha256', 'Contenido del aviso legal v1.0');

        $response = $this->postJson(route('consent.log'), [
            'user_id' => 1,
            'user_type' => 'profesional',
            'document_type' => 'aviso_legal',
            'document_version' => '1.0',
            'document_hash' => $hash,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'user_id',
                         'user_type',
                         'document_type',
                         'document_version',
                         'document_hash',
                         'ip_address',
                         'user_agent',
                         'accepted_at',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('consent_logs', [
            'user_id' => 1,
            'user_type' => 'profesional',
            'document_type' => 'aviso_legal',
            'document_version' => '1.0',
            'document_hash' => $hash,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_consent_log()
    {
        $response = $this->postJson(route('consent.log'), []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['document_type', 'document_version', 'document_hash']);
    }
}
