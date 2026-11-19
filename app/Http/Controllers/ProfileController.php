<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Desa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $viewData = ['user' => $user];

        if (! $user->isAdmin()) {
            $user->load('desas');
            $viewData['desas'] = Desa::query()
                ->with('kawasan:id,nama_kawasan')
                ->orderBy('kecamatan')
                ->orderBy('nama_desa')
                ->get(['id', 'kawasan_id', 'nama_desa', 'kecamatan', 'kabupaten']);
            $viewData['kecamatanOptions'] = $viewData['desas']
                ->pluck('kecamatan')
                ->filter(fn (?string $kecamatan): bool => filled($kecamatan))
                ->unique(fn (string $kecamatan): string => Desa::normalizeKecamatan($kecamatan))
                ->map(fn (string $kecamatan): array => [
                    'value' => $kecamatan,
                    'key' => Desa::normalizeKecamatan($kecamatan),
                ])
                ->values();
            $requestedKecamatan = $request->session()->getOldInput(
                'kecamatan_binaan',
                $user->kecamatan_binaan ?? '',
            );
            $selectedKecamatan = $viewData['kecamatanOptions']->first(
                fn (array $option): bool => $option['key'] === Desa::normalizeKecamatan($requestedKecamatan),
            );
            $viewData['selectedKecamatan'] = $selectedKecamatan['value'] ?? '';
            $viewData['selectedKecamatanKey'] = $selectedKecamatan['key'] ?? '';
            $viewData['desaOptions'] = $viewData['desas']
                ->map(fn (Desa $desa): array => [
                    'id' => (string) $desa->id,
                    'kecamatan_key' => Desa::normalizeKecamatan($desa->kecamatan),
                    'label' => implode(' — ', array_filter([
                        $desa->nama_desa,
                        $desa->kecamatan ?? 'Kecamatan belum tercatat',
                        $desa->kawasan?->nama_kawasan,
                    ])),
                ])
                ->filter(fn (array $desa): bool => $desa['kecamatan_key'] !== '')
                ->values()
                ->all();
            $viewData['selectedDesaIds'] = $user->desas
                ->pluck('id')
                ->map(fn (int $id): string => (string) $id)
                ->all();
        }

        return view('profile.edit', $viewData);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $updateWorkArea = (bool) ($validated['update_work_area'] ?? false);
        $desaIds = $validated['desa_binaan'] ?? [];

        unset($validated['update_work_area'], $validated['desa_binaan']);

        DB::transaction(function () use ($user, $validated, $updateWorkArea, $desaIds): void {
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($updateWorkArea) {
                $user->desas()->sync($desaIds);
            }
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
