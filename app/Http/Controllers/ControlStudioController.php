<?php
namespace App\Http\Controllers;

use App\Support\Plugins\PluginRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControlStudioController extends Controller
{
    public function __invoke(Request $request, PluginRegistry $registry): View
    {
        abort_unless($request->user()?->isOwner(), 403);
        return view('control-studio', ['user' => $request->user(), 'plugins' => $registry->states()]);
    }
}
