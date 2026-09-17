<?php

namespace Tests;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function officerUser(): User
    {
        $officer = User::officers()->first();

        if ($officer) {
            return $officer;
        }

        $area = Area::first();
        $officer = User::create([
            'name' => 'Test Officer',
            'email' => 'officer@ekota.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $officer->assignRole('field_officer');

        if ($area) {
            $officer->areas()->attach($area->id);
        }

        return $officer;
    }
}
