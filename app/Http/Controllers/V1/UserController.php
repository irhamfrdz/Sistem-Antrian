<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Loket;

class UserController extends Controller
{
    public function index()
    {
        $data = User::whereRole('staff')->get();
        return view('backend.user.index', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        $image = $request->file('foto');

        $filename='';
        if ($image != '') {
            $filename = uniqid().$image->getClientOriginalName();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'jabatan' => $request->jabatan,
            'foto' => $filename,
            'role' => "staff",
            'password' => bcrypt("12345678"),
            'email_verified_at' => date("Y-m-d H:i:s")
        ]);

        // PROSES UPLOAD
        if ($image != '') {
            $path = 'uploads/user/';
            $image->move($path,$filename);
        }

        Loket::create([
            'user_id' => $user->id,
            'kode' => $request->kode,
            'tujuan' => $request->tujuan,
            'status' => '0'
        ]);

        return back()->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request)
    {
        $data = User::find($request->user);
        if (empty($data)) {
            # code...
            return back()->with('galat', 'User Tidak Tersedia');
        }

        $data->update([
            'name' => $request->name,
            'email' => $request->email
        ]);

        return back()->with('success', 'User berhasil di update');
    }

    public function destroy(Request $request)
    {
        $data = User::find($request->user);
        if (empty($data)) {
            # code...
            return back()->with('galat', 'User Tidak Tersedia');
        }

        $data->delete();
        return back()->with('success', 'User berhasil dihapus');
    }
}
