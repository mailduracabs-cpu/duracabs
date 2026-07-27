<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user0 =  User::factory()->create([
            'name' => 'Test User',
            'email' => 'vksgpt50@gmail.com',
           
        ]);

        $user1 = User::factory()->create([
            'name' => 'VikasAdmin',
            'email' => 'mail.duracabs1@gmail.com',
           
        ]);

      

        $user2 =  User::factory()->create([
            'name' => 'transporter',
            'email' => 'transporter@example.com',
           
        ]);

        $user3 =  User::factory()->create([
            'name' => 'moderator',
            'email' => 'moderator@exaple.com',
           
        ]);

        $user4 =  User::factory()->create([
            'name' => 'driver',
            'email' => 'coolvikas1818@gmail.com',
        ]);

        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Transporter']);
        $role3 = Role::create(['name' => 'moderator']);
        $role4  = Role::create(['name' => 'Driver']);
        $role0  = Role::create(['name' => 'user']);

        $user1->assignRole( $role1 );
        $user2->assignRole( $role2 );
        $user3->assignRole( $role3 );
        $user4->assignRole( $role4 );
        $user0->assignRole( $role0 );

        $Permission1 = Permission::create(['name' => 'View Vehicle']);
        $Permission2 = Permission::create(['name' => 'Create Vehical']);
        $Permission3 = Permission::create(['name' => 'Update Vehical']);
        $Permission4 = Permission::create(['name' => 'Delete Vehical']);
        $Permission5 = Permission::create(['name' => 'View Product']);
        $Permission6 = Permission::create(['name' => 'Create Product']);
        $Permission7 = Permission::create(['name' => 'Delete Product']);
        $Permission8 = Permission::create(['name' => 'Update Product']);
        $Permission9 = Permission::create(['name' => 'View City']);
        $Permission10 = Permission::create(['name' => 'Create City']);
        $Permission11 = Permission::create(['name' => 'Update City']);
        $Permission12 = Permission::create(['name' => 'Delete City']);
        $Permission13 = Permission::create(['name' => 'View Category']);
        $Permission14 = Permission::create(['name' => 'Create Category']);
        $Permission15 = Permission::create(['name' => 'Update Category']);
        $Permission16 = Permission::create(['name' => 'Delete Category']);
        $Permission17 = Permission::create(['name' => 'View Order']);
        $Permission18 = Permission::create(['name' => 'Create Order']);
        $Permission19 = Permission::create(['name' => 'Update Order']);
        $Permission20 = Permission::create(['name' => 'Delete Order']);
        $Permission21 = Permission::create(['name' => 'View Links']);
        $Permission22 = Permission::create(['name' => 'Create Links']);
        $Permission23 = Permission::create(['name' => 'Update Links']);
        $Permission24 = Permission::create(['name' => 'Delete Links']);
        $Permission24 = Permission::create(['name' => 'View Review']);
        $Permission24 = Permission::create(['name' => 'Create Review']);
        $Permission24 = Permission::create(['name' => 'Update Review']);
        $Permission24 = Permission::create(['name' => 'Delete Review']);
        $Permission24 = Permission::create(['name' => 'View Inquiry']);
        $Permission24 = Permission::create(['name' => 'Create Inquiry']);
        $Permission24 = Permission::create(['name' => 'Update Inquiry']);
        $Permission24 = Permission::create(['name' => 'Delete Inquiry']);

        
    }
}
