@extends('master')

@section('content')
	@if(session('msg'))
		<div class="content-wrapper ambient-key-shadows action-result action-success">
			<span class="fa fa-times-circle fa-2x"></span>
			{{ session('msg') }}
		</div>
	@endif
	<div class="content-wrapper ambient-key-shadows">
		<div class="row">
			<div class="col-sm-12">
				<h3 class="text-center">{{ trans('common.topics') }}</h3>

				<div class="text-center">
					<a href="{{ route('translator.topics', ['filter' => 'all']) }}"
					   class="btn btn-primary">
						@if(isset($filter_all) && $filter_all)
							<span class="fa fa-check"></span>
						@endif
						{{ trans('common.all') }}
					</a>

					&nbsp;&nbsp;&nbsp;&nbsp;

					<a href="{{ route('translator.topics', ['filter' => 'my']) }}"
					   class="btn btn-primary">
						@if(isset($filter_my) && $filter_my)
							<span class="fa fa-check"></span>
						@endif
						{{ trans('common.my_topics') }}
					</a>

					&nbsp;&nbsp;&nbsp;&nbsp;

					<a href="{{ route('translator.topics', ['filter' => 'saved']) }}"
					   class="btn btn-primary">
						@if(isset($filter_saved) && $filter_saved)
							<span class="fa fa-check"></span>
						@endif
						{{ trans('common.saved_topics') }}
					</a>

					&nbsp;&nbsp;&nbsp;&nbsp;

					<a href="{{ route('translator.topics') }}" class="btn btn-primary">
						@if(isset($filter_untranslated_changed) && $filter_untranslated_changed)
							<span class="fa fa-check"></span>
						@endif
						{{ trans('common.untranslated_changed') }}
					</a>

					&nbsp;&nbsp;&nbsp;&nbsp;

					<a href="{{ route('translator.topics', ['filter' => 'untranslated']) }}"
					   class="btn btn-primary">
						@if(isset($filter_untranslated) && $filter_untranslated)
							<span class="fa fa-check"></span>
						@endif
						{{ trans('common.untranslated') }}
					</a>

					&nbsp;&nbsp;&nbsp;&nbsp;

					<a href="{{ route('translator.topics', ['filter' => 'changed']) }}"
					   class="btn btn-primary">
						@if(isset($filter_changed) && $filter_changed)
							<span class="fa fa-check"></span>
						@endif
						{{ trans('common.changed') }}
					</a>

				</div>

				<br />

				<div class="row">
					@if(isset($filter_my) && $filter_my)
					<div class="col-sm-12">
						<div class="translation-group">
							<h3 class="lang-name navbar-left">{{ trans('common.translator_search_topics_message') }}</h3>
							<form action="" method="post" id="translation-form">
								{!! csrf_field() !!}
								<div class="form-group">
									<input type="text" name="topic_keyword" value="{{ $topic_keyword }}" id="topic_keyword" class="form-control" autofocus/>
									<button type="submit" name="search" value="1" class="btn btn-primary" style="margin-left: 12px;">
										<i class="fa fa-search"></i>
										{{ trans('common.translator_search_key_label') }}
									</button>
								</div>
							</form>
						</div>
						<br /><br />
						<ul class="nav nav-tabs">
							<li role="presentation" class="{{ $activetab_incomplete }}"><a href="{{ route('translator.topics', ['filter' =>'my']) }}">Incomplete ({{ $incomplete_num }} - {{ $incomplete_num+$rejected_num }}/{{ $max_incomplete_topics }})</a></li>
							<li role="presentation" class="{{ $activetab_completed }}"><a href="{{ route('translator.topics', ['filter' =>'my', 'type' =>'completed']) }}">Completed ({{ $completed_num }})</a></li>
							<li role="presentation" class="{{ $activetab_rejected }}"><a href="{{ route('translator.topics', ['filter' =>'my', 'type' =>'rejected']) }}">Rejected ({{ $rejected_num }} - {{ $incomplete_num+$rejected_num }}/{{ $max_incomplete_topics }})</a></li>
						</ul>
						<br />
					</div>
					@endif
					@foreach($topics as $t)
						<div class="col-xs-12 col-sm-12 col-md-6 col-lg-4">
							@if($t->finished == 1 AND $t->inspection_result == 1)
							<a style="background-color:#DCEDC8;" href="{{ route('translator.translate', ['topic_id' => $t->id]) }}"
							@elseif($t->finished == 1 AND $t->inspection_result == -1)
							<a style="background-color:#ffcdd2;" href="{{ route('translator.translate', ['topic_id' => $t->id]) }}"
							@elseif($t->finished == 1 AND $t->inspection_result == 0)
							<a style="background-color:#FFF9C4;" href="{{ route('translator.translate', ['topic_id' => $t->id]) }}"
							@else
							<a style="background-color:#FAFAFA;" href="{{ route('translator.translate', ['topic_id' => $t->id]) }}"
							@endif
							   class="btn btn-block btn-default" style="position:relative;">
								{{ urldecode(str_replace("_", " ", $t->topic)) }}
								@if(isset($filter_saved) && $filter_saved)
									<i class="fa fa-trash remove-saved-topic" rdurl="{{ route('translator.topics', ['filter' => 'saved', 'ft' => '', 'del' => $t->id ]) }}"></i>
								@endif
								@if($t->edited_at)
								<br />{{ date('n/j/Y - H:i', $t->edited_at) }}
								@endif
							</a>
							<br />
						</div>
					@endforeach
				</div>

				<br />

				{!! $topics->render() !!}
			</div>
		</div>
	</div>

@endsection