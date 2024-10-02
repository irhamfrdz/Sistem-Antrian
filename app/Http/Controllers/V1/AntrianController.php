<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Loket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    public function index(Request $request)
    {

        $data = Antrian::query()
            ->with('loket')
            ->whereHas('loket', function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->whereStatus('wait')
            ->whereDate('created_at', Carbon::today())->first();

        if ($data) {
            $copy = Antrian::query()
            ->with('loket')
            ->whereHas('loket', function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->whereRaw("antrians.id < $data[id] AND status_call IN ('panggil','panggil_lagi')")->whereDate('created_at', Carbon::today())->first();

            if ($copy) {
                $data = $copy;
            }
        } else {
            $data = Antrian::query()
            ->with('loket')
            ->whereHas('loket', function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            // ->whereStatus('wait')
            ->whereDate('created_at', Carbon::today())->latest()->first();
        }

        // if ($data['status_call'] == "next") {
        //     $data = Antrian::query()
        //     ->with('loket')
        //     ->whereHas('loket', function ($query) {
        //         return $query->where('user_id', auth()->user()->id);
        //     })
        //     ->whereStatus('finish')
        //     ->whereDate('created_at', Carbon::today())->first();
        // }elseif ($data['status_call'] == "panggil") {
        //     $data = Antrian::query()
        //     ->with('loket')
        //     ->whereHas('loket', function ($query) {
        //         return $query->where('user_id', auth()->user()->id);
        //     })
        //     ->whereStatus('finish')
        //     ->where('status_call','panggil')
        //     ->whereDate('created_at', Carbon::today())->latest()->first();
        // } else {
        //     $data = Antrian::query()
        //     ->with('loket')
        //     ->whereHas('loket', function ($query) {
        //         return $query->where('user_id', auth()->user()->id);
        //     })
        //     ->whereStatus('wait')
        //     ->whereDate('created_at', Carbon::today())->first();
        // }

        $id = $data['id'] ?? 0;

        $next = Antrian::query()
            ->with('loket')
            ->whereHas('loket', function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->whereStatus('wait')
            ->whereRaw("antrians.id > $id")->first();

        // $loket = Loket::where('user_id', auth()->user()->id)->first();
        
        // if (empty($loket)) {
        //     # code...
        //     return redirect()->route('v1')->with('galat', 'Kamu tidak terdaftar di loket');
        // }

        // // dd($loket);
        // 
        
        // if ($request->isMethod('post')) {
        //     dd('s');
        // }
        
        
            
        if ($request->antrian) {
            $data->status = "active";
            $data->save();
            
            
            // dd($t);
            return redirect()->back();
            // if ($data == null) {
                # bikinin nomor antrian
                // Antrian::create([
                //     'loket_id' => $loket->id,
                //     'nomor' => $loket->kode . '001',
                //     'status' => 'active',
                // ]);
                

                // return redirect()->route('v1.antrian')->with('finish', 'panggil');
            // }
        }

        $g = Loket::where('user_id',auth()->user()->id)->first();
        $antri = Antrian::whereIn('status',['wait','finish'])->whereRaw("IFNULL(status_call,'') IN ('','panggil','panggil_lagi')")->whereDate('created_at', Carbon::today())->where('loket_id',$g->id)->get();

        return view('backend.antrian.index', compact('data','next','antri'));
    }

    public function lanjut(Request $request)
    {
        $nomor = strval(str_replace($request->kode, '', $request->antrian));
        $next = $nomor + 1;
        $str_length = 3;

        // hardcoded left padding if number < $str_length
        $str = substr("0000{$next}", -$str_length);
        $nomor = $request->kode . $str;

        $last = Antrian::whereNomor($request->antrian)->whereDate('created_at', Carbon::today())->first();

        $last->update([
            'status' => 'finish'
        ]);

        if ($request->status) {
            
            if($last->status_call == 'panggil') {
            //     $d = date('Y-m-d H:i:s');
            // $t = Antrian::where('nomor',$data->nomor)->update(['status_call' => 'panggil_lagi']);
                $last->update([
                    'status_call' => 'panggil_lagi'
                ]);
            } else {
                $last->update([
                    'status_call' => 'panggil'
                ]);
            }
            
        } else {
            $last->update([
                'status_call' => 'next'
            ]);

            $next = Antrian::query()
            ->with('loket')
            ->whereHas('loket', function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->whereStatus('wait')
            ->whereRaw("antrians.id > $last->id")->first();

            if ($next) {
                $next->update([
                    'status' => 'finish',
                    'status_call' => 'panggil'
                ]);
            }

            // $last = Antrian::whereNomor($nomor)->first();

            // $last->update([
            //     'status' => 'finish'
            // ]);
        }

        $loket = Loket::where('user_id', auth()->user()->id)->first();

        // Antrian::create([
        //     'loket_id' => $loket->id,
        //     'nomor' => $loket->kode . $str,
        //     'status' => 'active',
        // ]);

        return redirect()->route('v1.antrian')->with('finish', 'panggil');

        // dd($str);
    }

    public function history(Request $request) {
        $user = auth()->user();
        $loket = Loket::where('user_id',$user->id)->first();
        $antrian = Antrian::where('loket_id',$loket->id)->latest()->get();

        if ($request->dari) {
            $antrian = Antrian::where('loket_id',$loket->id)->whereDate('created_at','>=',$request->dari)->whereDate('created_at','<=',$request->sampai)->latest()->get();
        }

        return view('backend.antrian.history', compact('antrian'));
    }
}
