<?php

namespace App\Http\Controllers\Administrador;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\PeriodoNotasRequest;
use App\Http\Requests\RegistroNotasRequest;
use App\Http\Controllers\Controller;
use Redirect;
use Session;
use App\Core\Repositories\Administrador\NotasRepo;
use App\Core\Entities\Cursos;
use App\Core\Entities\Alumno;
use App\Core\Entities\Seccion;
use App\Core\Entities\NotaCurso;
use App\Core\Entities\NotaTarjeta;
use App\Core\Entities\Tarjeta;
use DB;
use Auth;

class NotasController extends Controller
{
    protected $NotasRepo;
    public function __construct(NotasRepo $NotasRepo)
    {
        $this->NotasRepo = $NotasRepo;
    }

    public function index()
    {
        $lastPeriodo = $this->NotasRepo->getLastPeriodoMatricula();
        if(count($lastPeriodo) > 0)
        {
          $cursospe = DB::table('profesorcurso as pc')
            ->select('pc.idprofesorcurso','cu.idcurso','cu.nombre','ps.idseccion','s.nombre as seccion','g.nombre as grado','n.nombre as nivel','sd.nombre as sede')
            ->leftJoin('curso as cu','cu.idcurso','=','pc.idcurso')
            ->leftJoin('profesorseccion as ps','ps.idprofesorcurso','=','pc.idprofesorcurso')
            ->Join('seccion as s','s.idseccion','=','ps.idseccion')
            ->Join('grado as g','g.idgrado','=','s.idgrado')
            ->Join('nivel as n','n.idnivel','=','s.idnivel')
            ->Join('sede as sd','sd.idsede','=','s.idsede')
            ->where('pc.idperiodomatricula',$lastPeriodo[0]->idperiodomatricula)
            ->where('pc.iduser', Auth::user()->id)
            ->where('cu.deleted_at', null)
            ->groupBy('pc.idprofesorcurso')
            ->get();


          $tutorias = DB::table('profesortutoria as pt')
            ->select('s.nombre as seccion','s.idseccion as idsection','g.nombre as grado','n.nombre as nivel','sd.nombre as sede')
              ->leftJoin('seccion as s','s.idseccion','=','pt.idseccion')
              ->leftJoin('grado as g','g.idgrado','=','s.idgrado')
              ->leftJoin('nivel as n','n.idnivel','=','g.idnivel')
              ->leftJoin('sede as sd','sd.idsede','=','n.idsede')
              ->where('pt.idperiodomatricula',$lastPeriodo[0]->idperiodomatricula)
              ->where('pt.idprofesor', Auth::user()->id)
              ->get();
          $datehow = Date('Ymd');
          $fechanota = $this->NotasRepo->getFechaNota($lastPeriodo[0]->idperiodomatricula, $datehow);
          return view('administrador.notas.list', compact('tutorias','cursospe','fechanota'));
        }
        else
        {
            return view('matricula.periodo.not');
        }
    }

    public function create()
    {
        $bimestres = $this->NotasRepo->getBimestre();
        $periodonotas = $this->NotasRepo->periodoNotas();
        return view('administrador.notas.index',compact('bimestres','periodonotas'));
    }

    public function store(PeriodoNotasRequest $request)
    {
        $lastPeriodo = $this->NotasRepo->getLastPeriodoMatricula();
        $fechanotas = $this->NotasRepo->SaveFechaNota($request->all(), $lastPeriodo[0]->idperiodomatricula);

        if($fechanotas){
            Session::flash('message-success', 'Se registro correctamente las fechas para subir las notas');
            return Redirect::back();
        }
        else{
            Session::flash('message-danger', 'Ocurrio un error al validar los campos');
            return Redirect::back()->withInput();
        }
    }

    public function register($idcurso, $idseccion)
    {
        $datenow = Date('Ymd');
        $lastPeriodo = $this->NotasRepo->getLastPeriodoMatricula();
        $tutoria = DB::table('profesortutoria')
          ->where('idseccion',$idseccion)
          ->where('idperiodomatricula', $lastPeriodo[0]->idperiodomatricula)
          ->where('idprofesor', Auth::user()->id)
          ->get();

        $datape = DB::table('curso')
            ->select('curso.*')
            ->leftJoin('grado as g','g.idgrado','=','curso.idgrado')
            ->where('idcurso', $idcurso)
            ->take(1)
            ->get();



        $alumnos = $this->NotasRepo->getAlumnos($idcurso, $datape[0]->idgrado, $idseccion, $lastPeriodo[0]->idperiodomatricula);
        $fechanota = $this->NotasRepo->getFechaNota($lastPeriodo[0]->idperiodomatricula, $datenow);
        $namecurso = Cursos::find($idcurso);
        $seccion = Seccion::with('grado')->with('nivel')->with('sede')->where('idseccion',$idseccion)->first();


        if(count($fechanota)>0)
        {
            return view('administrador.notas.register', compact('tutoria','alumnos','fechanota','lastPeriodo', 'idcurso','idseccion','namecurso','seccion'));
        }
        else{
            Session::flash('message-danger', ' aun no puedes subir las notas, te encuentras fuera de fecha.');
            return Redirect::back();
        }
    }

    public function registerNotas(RegistroNotasRequest $request)
    {
        for ($i=0; $i < count($request['idalumno']); $i++) {
            $notaNumber = 0;
            $notaChar = 0;
            if(is_numeric($request['bimestreINota'][$i])){
                $notaNumber = $request['bimestreINota'][$i];
            }
            else{
                $notaChar = $request['bimestreINota'][$i];
            }

            $notacurso = NotaCurso::where('idalumno',$request['idalumno'][$i])
              ->where('idperiodomatricula', $request['idperiodo'])
              ->where('idcurso',$request['idcurso'])
              ->first();

            if(!$notacurso){
                $notacurso = new NotaCurso;
            }
            $notacurso->idbimestre         = $request['idbimestre'];
            $notacurso->idperiodomatricula = $request['idperiodo'];
            $notacurso->idcurso            = $request['idcurso'];
            $notacurso->idseccion          = $request['idseccion'];
            $notacurso->nota_number        = $notaNumber;
            $notacurso->nota_char          = $notaChar;
            $notacurso->idalumno           = $request['idalumno'][$i];
            $notacurso->usercreate         = Auth::user()->id;
            $notacurso->updated_at         = '';
            $notacurso->save();
        }
        Session::flash('message-success', ' La notas se han registrado con éxito.');
        return Redirect::back();
    }

    public function edit($id)
    {
        $bimestres = $this->NotasRepo->getBimestre();
        $periodonotas = $this->NotasRepo->showFechanotas($id);
        return view('administrador.notas.edit_periodo', compact('bimestres','periodonotas'));
    }

    public function update(Request $request, $id)
    {
        $periodonotas = $this->NotasRepo->updatePeriodoNota($request->all(), $id);

        if($periodonotas){
            Session::flash('message-success', 'Se actualizo correctamente el periodo de notas');
            return redirect()->route('fechanotas');
        }
        else{
            Session::flash('message-danger', 'Ocurrio un error al actualizar el periodo de notas');
            return redirect()->route('fechanotas');
        }
    }

    public function destroy($id)
    {
        $fechanotas = $this->NotasRepo->deleteFechanotas($id);
        if($fechanotas)
        {
            Session::flash('message-success', 'La ha sido eliminado la fecha de nota');
            return redirect()->route('fechanotas');
        }
        else{
            return redirect()->back()->withInput();
        }
    }

    public function registerTarjetaNotas()
    {

        $lastPeriodo = $this->NotasRepo->getLastPeriodoMatricula();
        $datehow = Date('Ymd');
        $fechanota = $this->NotasRepo->getFechaNota($lastPeriodo[0]->idperiodomatricula, $datehow);
        //Forcing alumno 1
        $alumno = 1;

        $alumno = Alumno::with('matricula')->where('idalumno',$alumno)->first();
        //Load Tarjeta
        $tarjeta = Tarjeta::with('tarjetabloque')->where('idnivel',$alumno->matricula->idnivel)->first();

        $qnotas = NotaTarjeta::where('idtarjeta',$tarjeta->idtarjeta)
                            ->where('idalumno',$alumno->idalumno)
                            ->where('idbimestre',$fechanota[0]->idbimestre)
                            ->where('idperiodomatricula',$alumno->matricula->idperiodomatricula)
                            ->get();
        $notas = array();

        foreach ($qnotas as $nota) {
            $notas[$nota->idbloquecriterio] = $nota;
        }

        return view('administrador.notas.newnotatarjeta',compact('alumno','tarjeta','fechanota','notas'));
    }

    public function tarjetanotas(Request $request){


        $alumno = $request->input('alumno');
        if($alumno){
            $alumno = 1;
            $datehow = Date('Ymd');
            $alumno = Alumno::with('matricula')->where('idalumno',$alumno)->first();
            $tarjeta = Tarjeta::with('tarjetabloque')->where('idnivel',$alumno->matricula->idnivel)->first();
            $lastPeriodo = $this->NotasRepo->getLastPeriodoMatricula();
            $fechanota = $this->NotasRepo->getFechaNota($lastPeriodo[0]->idperiodomatricula, $datehow);
            foreach($request->input('nota') as $key => $nota){
                $data = explode('-',$key);
                if(isset($nota['id'])){

                    $notaTarjeta = NotaTarjeta::find($nota['id']);
                }else{
                    $notaTarjeta = new NotaTarjeta();
                }
                $notaTarjeta->S = $nota['S'];
                $notaTarjeta->CS = $nota['CS'];
                $notaTarjeta->AV = $nota['AV'];
                $notaTarjeta->N = $nota['N'];
                $notaTarjeta->idtarjeta = $tarjeta->idtarjeta;
                $notaTarjeta->idbloque = $data[0];
                $notaTarjeta->idbloquecriterio = $data[1];
                $notaTarjeta->idbimestre = $fechanota[0]->idbimestre;
                $notaTarjeta->idperiodomatricula = $alumno->matricula->idperiodomatricula;
                $notaTarjeta->idtutor = Auth::user()->id;
                $notaTarjeta->idalumno = $alumno->idalumno;
                $notaTarjeta->save();
            }
        }

       return redirect()->route('tarjetanotas');

    }



}






