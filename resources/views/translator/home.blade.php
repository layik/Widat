@extends('master')

@section('content')
	<div class="content-wrapper ambient-key-shadows">
		<div class="row">
			<div class="col-sm-12">
				<div class="row">
					<div class="col-sm-12 col-md-3">
						<h3 class="text-center">{{ trans('common.score') }}</h3>
						<p class="count text-center text-info">
							{{ number_format($score) }}
						</p>
					</div>
					<div class="col-sm-12 col-md-3">
						<h3 class="text-center">{{ trans('common.nocandos') }}</h3>
						<p class="count text-center text-danger">
							{{ number_format($nocandos) }}
						</p>
					</div>
					<div class="col-sm-12 col-md-3">
						<h3 class="text-center">{{ trans('common.translated') }}</h3>
						<p class="count text-center">
							{{ number_format($translated) }}
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection