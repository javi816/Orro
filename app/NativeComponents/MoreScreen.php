<?php

namespace App\NativeComponents;

use App\Services\LocalAuthService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class MoreScreen extends NativeComponent
{
    public function onBackPressed(): void
    {
        $this->replace('/');
    }

    public function openIncomes(): void
    {
        $this->navigate('/ingresos');
    }

    public function openBudget(): void
    {
        $this->navigate('/presupuesto');
    }

    public function openPeriods(): void
    {
        $this->navigate('/periodos');
    }

    public function openSettings(): void
    {
        $this->navigate('/configuracion');
    }

    public function lock(): void
    {
        LocalAuthService::lock();
        $this->replace('/lock');
    }

    public function render(): View
    {
        return view('native.more');
    }
}
