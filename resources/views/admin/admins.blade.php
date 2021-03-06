@extends('master')

@section('content')
	@if(isset($action_result) && $action_result)
		@if($action_error)
			<div class="content-wrapper ambient-key-shadows action-result action-error">
		@else
			<div class="content-wrapper ambient-key-shadows action-result action-success">
		@endif
			<span class="fa fa-times-circle fa-2x"></span>
			{{ $action_result }}
		</div>
	@endif

	<div class="content-wrapper ambient-key-shadows">
		<div class="row">
			<div class="col-sm-12">
				<h2 class="text-center">
					<a href="javascript:;" class="toggle-down-sibling">
						<span class="fa fa-plus"></span>
						{{ trans('common.add_admin') }}
					</a>
				</h2>
				<form action="" method="post" class="hidden">
					{!! csrf_field() !!}
					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-6 col-md-6 col-lg-6">
							<div class="input-group">
								<label for="name" class="input-group-addon">
									{{ trans('common.name') }}
								</label>
								<input type="text" class="form-control" name="name" id="name"
										value="{{ old('name') }}" autofocus/>
							</div>
							<br />
						</div>

						<div class="col-xs-12 col-sm-12 col-md-6 col-md-6 col-lg-6">
							<div class="input-group">
								<label for="surname" class="input-group-addon">
									{{ trans('common.surname') }}
								</label>
								<input type="text" class="form-control" name="surname" id="surname"
									   value="{{ old('surname') }}"/>
							</div>
							<br/>
						</div>

						<div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
							<div class="input-group">
								<label for="email" class="input-group-addon">
									{{ trans('common.email') }}
								</label>
								<input type="email" class="form-control" name="email" id="email"
									   value="{{ old('email') }}"/>
							</div>
							<br />
						</div>

						<div class="col-xs-12 col-sm-12 col-md-6 col-md-6 col-lg-6">
							<div class="input-group">
								<label for="password" class="input-group-addon">
									{{ trans('common.password') }}
								</label>
								<input type="password" class="form-control" name="password"
									   id="password" value="{{ old('password') }}" />
								<div class="input-group-addon toggle-password">
									<span class="fa fa-eye-slash"></span>
								</div>
							</div>
							<br />
						</div>

						<div class="col-xs-12 col-sm-12 col-md-6 col-md-6 col-lg-6 pull-right">
							<div class="input-group">
								<label for="password" class="input-group-addon">
									{{ trans('common.password_confirm') }}
								</label>
								<input type="password" class="form-control" name="password_confirmation"
									   id="password_confirmation" />
								<div class="input-group-addon toggle-password">
									<span class="fa fa-eye-slash"></span>
								</div>
							</div>
							<br />
						</div>

						<div class="col-xs-12 col-sm-12 col-md-4 col-md-offset-4">
							<button name="create" value="1" class="btn btn-success btn-block">
								<span class="fa fa-plus"></span>
								<span class="text-uppercase">
									{{ trans('common.add_admin') }}
								</span>
							</button>
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>

	<div class="content-wrapper ambient-key-shadows">
		<div class="row">
			<div class="col-sm-12">
				<h2 class="text-center">
					<span class="fa fa-certificate"></span>
					{{ trans('common.admins') }}
				</h2>
				<div class="table-responsive">
					<table class="table table-striped">
						<thead>
							<th>{{ trans('common.id') }}</th>
							<th>{{ trans('common.email') }}</th>
							<th>{{ trans('common.name') }}</th>
							<th>{{ trans('common.last_activity') }}</th>
							<th></th>
						</thead>
						<tbody>
						@foreach($admins as $admin)
							<tr>
								<td>{{ $admin->id }}</td>
								<td>{{ $admin->email }}</td>
								<td>{{ $admin->name . ' ' . $admin->surname }}</td>
								<td>
									@if(strtotime($admin->last_activity) < 0)
										NEVER
									@else
										{{date('Y-m-d H:i:s e', strtotime($admin->last_activity))}}
									@endif
								</td>
								<td class="text-right">
									<a href="javascript:;" class="btn btn-danger delete-user">
										<span class="fa fa-trash"></span>
										{{ trans('common.delete') }}
										<input type="hidden" name="user_id" value="{{ $admin->id }}" />
									</a>
									&nbsp;&nbsp;&nbsp;&nbsp;
									<a href="{{ route('main.edit_account', ['user_id'=>$admin->id]) }}"
									   class="btn btn-default">
										<span class="fa fa-pencil"></span>
										{{ trans('common.edit_account') }}
									</a>
								</td>
							</tr>
						@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade delete-user-modal">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">{{ trans('common.delete') }}?</h4>
				</div>
				<div class="modal-body">
					<p>{{ trans('common.confirm_deletion') }}</p>
				</div>
				<div class="modal-footer">
					<form class="form-inline" action="" method="post">
						{!! csrf_field() !!}
						<input type="hidden" name="user_id" />
						<button type="button" class="btn btn-default" data-dismiss="modal">
							<span class="fa fa-smile-o"></span>
							{{ trans('common.cancel') }}
						</button>
						<button type="submit" class="btn btn-danger" name="delete" value="1">
							<span class="fa fa-trash"></span>
							{{ trans('common.delete') }}
						</button>
					</form>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
@endsection