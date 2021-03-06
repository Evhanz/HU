@extends('layouts.profesor')

@section('cuerpo')
	<div class="col-lg-12">

	<section class="panel">
		<header class="panel-heading">
		<h2 class="panel-title">Cursos</h2>
		</header>
		<div class="panel-body">
		@if(count($fechanota) > 0)
			<div class="alert alert-info">
				<ul class="fa-ul">
					<li>
						<i class="fa fa-info-circle fa-lg fa-li va-middle" style="line-height: 20px;"></i>
						<span class="va-middle">Tiene desde el {!! $fechanota[0]->fecha_inicio !!}, hasta el {!! $fechanota[0]->fecha_fin !!}  para registrar las notas de los alumno.</span>
					</li>
				</ul>
			</div>
		@endif
		
		@if($tutorias)
		<section class="panel">
			<div class="panel-body bg-quartenary">
				<div class="widget-summary">
					<div class="widget-summary-col">
						<div class="summary">
							<h4 class="title">Tutorias a cargo:</h4>
							<div class="info">
								@foreach($tutorias as $tutoria)
								<div class="col-md-3">
									<a href="{{ route('tutorialist', $tutoria->idsection) }}" style="color: #FFFA15"><strong> > Sección: {{ $tutoria->seccion }}</strong></a><br>
									<span style="font-size: 11px;">{{ $tutoria->sede }} | {{ $tutoria->nivel }} | {{ $tutoria->grado }}</span>
								</div>
								@endforeach
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		@endif
			@include('alertas.request')
			@include('alertas.error')
			
			@foreach($cursospe as $data)
				@if($data->idseccion)
	                <div class="col-md-6 col-lg-6 col-xl-3">
	                    <section class="panel panel-featured-bottom panel-featured-primary">
	                        <div class="panel-body">
	                    <div class="widget-summary widget-summary-xs">
	                        <div class="widget-summary-col widget-summary-col-icon">
	                            <div class="summary-icon bg-primary">
	                                <i class="fa fa-life-ring"></i>
	                            </div>
	                        </div>
	                        <div class="widget-summary-col">
	                            <div class="summary">
	                                <h4 class="title">
	                                    <a href="{{ route('addnotas', [$data->idcurso, $data->idseccion]) }}">{!! $data->nombre !!}</a>
	                                </h4>
									<span style="font-size: 11px;">{{ $data->sede }} | {{ $data->nivel }} | {{ $data->grado }} | {{ $data->seccion }}</span>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	                    </section>
	                </div>
	            @endif
			@endforeach
		</div>
	</section>
</div>
@endsection
