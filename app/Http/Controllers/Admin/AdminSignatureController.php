<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminSignatureController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('Admin/Signature', [
            'hasSignature' => $request->user()->signature_path
                && Storage::disk('local')->exists($request->user()->signature_path),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:min_width=50,min_height=20,max_width=2400,max_height=1200'],
        ]);

        $disk = Storage::disk('local');
        $path = $request->file('signature')->store('agent-signatures', 'local');
        abort_unless($path, 500, 'Die Unterschrift konnte nicht gespeichert werden.');
        $old = $request->user()->signature_path;
        $request->user()->forceFill(['signature_path' => $path])->save();
        if ($old) {
            $disk->delete($old);
        }

        return back()->with('success', 'Deine Unterschrift wurde gespeichert.');
    }

    public function show(Request $request)
    {
        $path = $request->user()->signature_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store']);
    }

    public function destroy(Request $request)
    {
        $path = $request->user()->signature_path;
        $request->user()->forceFill(['signature_path' => null])->save();
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Deine Unterschrift wurde entfernt. Bereits erstellte Verträge bleiben erhalten.');
    }
}
