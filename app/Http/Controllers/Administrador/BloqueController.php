<?php

namespace App\Http\Controllers\Administrador;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\TarjetaRequest;
use App\Http\Controllers\Controller;
use App\Core\Repositories\Administrador\BloqueRepo;
use Session;
use Redirect;

class BloqueController extends Controller
{
    private $path = "administrador";
    private $subpath = "tarjetas";
    private $insubpath = "bloquecriterio";
    protected $BloqueRepo;

    public function __construct(BloqueRepo $BloqueRepo)
    {
        $this->BloqueRepo = $BloqueRepo;
    }

    public function index()
    {

        $bloque = $this->BloqueRepo->getBloques();
        return view("{$this->path}.{$this->subpath}.{$this->insubpath}.index", compact('bloque'));
    }

    public function create()
    {
        $tarjetas = $this->BloqueRepo->getTarjetas();
        return view("{$this->path}.{$this->subpath}.{$this->insubpath}.new", compact('tarjetas'));
    }

    public function store(Request $request)
    {
        $tarjeta = $this->BloqueRepo->SaveTarjeta($request->all());
        if($tarjeta){
            Session::flash('message-success', 'Se registro correctamente la tarjeta');            
            return redirect()->route('tarjetas');
        }
        else{
            Session::flash('message-danger', 'Ocurrio un error al validar el registro');            
            return redirect()->route('tarjetanew');
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
