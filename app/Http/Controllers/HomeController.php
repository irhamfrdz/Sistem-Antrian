<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Loket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $userIds = User::where('is_login','1')->where('role','staff')->pluck('id');
        $data = Loket::whereIn('user_id',$userIds)->get();
        $loket = Loket::where('status','0')->whereIn('user_id',$userIds)->get();

        $last = Antrian::whereIn('status',['finish'])->whereRaw("IFNULL(status_call,'') IN ('panggil')")->whereDate('created_at', Carbon::today())->orderBy('updated_at','DESC')->first();

        $antri = Antrian::whereIn('status',['wait','finish'])->whereRaw("IFNULL(status_call,'') IN ('','panggil')")->whereDate('created_at', Carbon::today())->orderBy('loket_id')->get();

        return view('home.index', compact('data','loket','antri','last'));
    }

    public function display2()
    {
        $userIds = User::where('is_login','1')->where('role','staff')->pluck('id');
        $data = Loket::whereIn('user_id',$userIds)->get();
        $loket = Loket::where('status','0')->whereIn('user_id',$userIds)->get();

        $last = Antrian::whereIn('status',['finish'])->whereRaw("IFNULL(status_call,'') IN ('panggil')")->whereDate('created_at', Carbon::today())->orderBy('updated_at','DESC')->first();

        $antri = Antrian::whereIn('status',['wait','finish'])->whereRaw("IFNULL(status_call,'') IN ('','panggil')")->whereDate('created_at', Carbon::today())->orderBy('loket_id')->get();

        return view('home.display2', compact('data','loket','antri','last'));
    }

    public function store(Request $request)
    {
        $data =
            Antrian::query()
            ->with('loket')
            ->where([
                'loket_id' => $request->nomor,
                'status' => 'finish'
            ])
            ->whereDate('created_at', Carbon::today())
            ->latest('nomor')->first();

        $last =
            Antrian::query()
            ->with('loket')
            ->where([
                'loket_id' => $request->nomor,
            ])
            ->whereDate('created_at', Carbon::today())
            ->latest('nomor')->first();

        return response()->json([
            'data1' => $data->nomor.'/'.$last->nomor??'-',
            'data2' => $data
        ]);
    }
    
    public function last(Request $request)
    {
        $data =
            Antrian::query()
            ->with('loket')
            ->where([
                // 'loket_id' => $request->nomor,
                'status' => 'finish'
            ])
            ->orWhere([
                // 'loket_id' => $request->nomor,
                'status_call' => 'panggil_lagi'
            ])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('updated_at', 'DESC')
            ->orderBy('nomor', 'DESC')
            ->first();

        $last =
            Antrian::query()
            ->with('loket')
            ->where([
                'loket_id' => $data->loket_id,
            ])
            ->whereDate('created_at', Carbon::today())
            ->latest('nomor')->first();
        
        return response()->json([
            'data1' => $data->nomor.'/'.$last->nomor??'-',
            'data2' => $data
        ]);
    }
    
    public function updatestatus($id)
    {
        Antrian::where('id',$id)->update(['status_call' => 'panggil']);
        
        echo 'sukses'; 
    }

    public function viewBuatAntrian() {
        $userIds = User::where('is_login','1')->where('role','staff')->pluck('id');
        $data = Loket::whereIn('user_id',$userIds)->get();
        $loket = Loket::where('status','0')->whereIn('user_id',$userIds)->get();

        return view('home.antrian', compact('data','loket'));
    }

    public function buatAntrian(Request $request) {
        $data = $request->all();

        $get = Loket::find($data['loket_id']);
        $antrian = Antrian::whereRaw("LEFT(nomor, 1)='$get->kode'")->whereDate('created_at', Carbon::today())->latest()->first();

        $nomor = "";

        if ($antrian) {
            $nomor = strval(str_replace($get->kode, '', $antrian->nomor));
            $next = $nomor + 1;
            $str_length = 3;

            // hardcoded left padding if number < $str_length
            $str = substr("0000{$next}", -$str_length);

            // $last = Antrian::whereNomor($antrian->nomor)->first();
            // $last->update([
            //     'status' => 'finish'
            // ]);

            $nomor = $get->kode . $str;

            Antrian::create([
                "nama" => $data['nama'],
                "nim" => $data['nim'],
                "jurusan" => $data['jurusan'],
                "keperluan" => $data['keperluan'],
                'loket_id' => $get->id,
                'nomor' => $nomor,
                'status' => 'wait',
            ]);
        } else {
            $nomor = $get->kode . '001';

            Antrian::create([
                "nama" => $data['nama'],
                "nim" => $data['nim'],
                "jurusan" => $data['jurusan'],
                "keperluan" => $data['keperluan'],
                'loket_id' => $get->id,
                'nomor' => $nomor,
                'status' => 'wait',
            ]);
        }

        return redirect()->back()->with(["success" => "Berhasil Membuat Antrian","nomor" => $nomor]);
    }
    
    public function getonline() {
        $userIds = User::where('is_login','1')->where('role','staff')->pluck('id');
        $data = Loket::whereIn('user_id',$userIds)->get();
        
        foreach($data as $key => $item) {
            $first =
            Antrian::query()
            ->with('loket')
            ->where([
                'loket_id' => $item->id,
                'status' => 'finish'
            ])
            ->whereDate('created_at', Carbon::today())
            ->latest('nomor')->first();

            $last =
                Antrian::query()
                ->with('loket')
                ->where([
                    'loket_id' => $item->id,
                ])
                ->whereDate('created_at', Carbon::today())
                ->latest('nomor')->first();

            $data[$key]['antrian'] = $first;
            if ($first == "") {
                $data[$key]['urut'] = '-';
            } else {
                $data[$key]['urut'] = $first->nomor.'/'.$last->nomor??'-';
            }
            
        }

        echo json_encode($data);
    }
    
    public function getdosen() {
        $userIds = User::where('is_login','1')->where('role','staff')->pluck('id');
        $data = Loket::whereIn('user_id',$userIds)->get();
        
        echo json_encode($data);
    }
    
}
