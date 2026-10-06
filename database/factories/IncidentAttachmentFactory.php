<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentAttachment>
 */
class IncidentAttachmentFactory extends Factory
{
    protected $model = IncidentAttachment::class;

    public function definition(): array
    {
        $files = [
            ['captura_cctv_sin_senal.jpg', 'image/jpeg', 350240],
            ['diag_puerto_switch.txt', 'text/plain', 12400],
            ['bitacora_filtro_2.pdf', 'application/pdf', 1048576],
            ['error_lector_biometrico.png', 'image/png', 524288],
        ];

        $selected = fake()->randomElement($files);

        return [
            'incident_id' => Incident::inRandomOrder()->value('id') ?? Incident::factory(),
            'uploaded_by_user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'original_filename' => $selected[0],
            'stored_path' => 'attachments/'.fake()->uuid().'_'.$selected[0],
            'mime_type' => $selected[1],
            'file_size_bytes' => $selected[2],
        ];
    }
}
