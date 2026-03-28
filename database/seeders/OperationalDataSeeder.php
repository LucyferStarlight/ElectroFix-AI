<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\TechnicianProfile;
use App\Support\OrderStatus;
use App\Support\TechnicianStatus;
use Illuminate\Database\Seeder;

class OperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('name', 'ElectroFix Cliente Demo')->firstOrFail();
        $developerLab = Company::query()->where('name', 'ElectroFix Developer Lab')->firstOrFail();

        $customerA = Customer::query()->updateOrCreate(
            ['company_id' => $company->id, 'email' => 'cliente1@demo.com'],
            ['name' => 'Empresa Norte', 'phone' => '+52 55 3000 1000', 'address' => 'CDMX, México']
        );
        $customerB = Customer::query()->updateOrCreate(
            ['company_id' => $company->id, 'email' => 'cliente2@demo.com'],
            ['name' => 'Cadena Sur', 'phone' => '+52 55 3000 2000', 'address' => 'Puebla, México']
        );

        $devCustomer = Customer::query()->updateOrCreate(
            ['company_id' => $developerLab->id, 'email' => 'lab@electrofix.ai'],
            ['name' => 'Developer Lab Client', 'phone' => '+52 55 5555 5555', 'address' => 'Monterrey, México']
        );

        $eqA = Equipment::query()->updateOrCreate(
            ['company_id' => $company->id, 'serial_number' => 'EF-LAV-001'],
            ['customer_id' => $customerA->id, 'type' => 'Lavadora', 'brand' => 'Samsung', 'model' => 'WF45R6100']
        );
        $eqB = Equipment::query()->updateOrCreate(
            ['company_id' => $company->id, 'serial_number' => 'EF-REF-002'],
            ['customer_id' => $customerB->id, 'type' => 'Refrigerador', 'brand' => 'LG', 'model' => 'GR-X24']
        );

        Equipment::query()->updateOrCreate(
            ['company_id' => $developerLab->id, 'serial_number' => 'DEV-TEST-001'],
            ['customer_id' => $devCustomer->id, 'type' => 'Microondas', 'brand' => 'Whirlpool', 'model' => 'WMX20']
        );

        $technicianA = TechnicianProfile::query()->updateOrCreate(
            ['company_id' => $company->id, 'display_name' => 'Operador A'],
            [
                'employee_code' => 'OP-A',
                'status' => TechnicianStatus::AVAILABLE,
                'max_concurrent_orders' => 5,
                'hourly_cost' => 0,
                'is_assignable' => true,
            ]
        );

        $technicianB = TechnicianProfile::query()->updateOrCreate(
            ['company_id' => $company->id, 'display_name' => 'Operador B'],
            [
                'employee_code' => 'OP-B',
                'status' => TechnicianStatus::AVAILABLE,
                'max_concurrent_orders' => 5,
                'hourly_cost' => 0,
                'is_assignable' => true,
            ]
        );

        Order::query()->updateOrCreate(
            ['company_id' => $company->id, 'equipment_id' => $eqA->id, 'technician' => 'Operador A'],
            [
                'customer_id' => $customerA->id,
                'technician_profile_id' => $technicianA->id,
                'symptoms' => 'No enciende y presenta ruido intermitente',
                'status' => OrderStatus::DIAGNOSING,
                'estimated_cost' => 1500,
                'ai_potential_causes' => ['Falla en fuente de alimentación', 'Posible desgaste mecánico'],
                'ai_estimated_time' => '3-5 horas',
                'ai_suggested_parts' => ['Tarjeta electrónica', 'Rodamientos'],
                'ai_technical_advice' => 'Verificar voltajes y continuidad antes de cambiar componentes.',
            ]
        );

        Order::query()->updateOrCreate(
            ['company_id' => $company->id, 'equipment_id' => $eqB->id, 'technician' => 'Operador B'],
            [
                'customer_id' => $customerB->id,
                'technician_profile_id' => $technicianB->id,
                'symptoms' => 'Fuga de agua por la parte inferior',
                'status' => OrderStatus::QUOTED,
                'estimated_cost' => 900,
                'ai_potential_causes' => ['Deterioro en sellos', 'Falla en manguera de drenaje'],
                'ai_estimated_time' => '1-2 horas',
                'ai_suggested_parts' => ['Kit de sellos', 'Manguera de drenaje'],
                'ai_technical_advice' => 'Confirmar presión y estado de juntas antes del reemplazo.',
            ]
        );
    }
}
