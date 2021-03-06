<?php
/**
 * Created by PhpStorm.
 * User: mnvoh
 * Date: 11/9/15
 * Time: 6:54 PM
 */

namespace App\Http\Controllers;


use App\KuTranslation;
use App\ScoreHistory;
use App\Topic;
use App\User;
use App\DeleteRecommendation;
use App\Securitycheck;
use App\Category;
use App\Categorylinks;
use App\SavedTopics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Validator;

class AdminController extends Controller
{
	public function __construct()
	{
		if(!Auth::check() || Auth::user()->user_type != "admin") {
			abort(401, "Unauthorized!");
		}

		if(Auth::check()) {
			$user = Auth::user();
			$user->last_activity = date('Y-m-d H:i:s');
			$user->save();
		}
		
		view()->share('delete_recommendations_num', 
			DeleteRecommendation::where('viewed', 0)
			->select('delete_recommendations.topic_id', 'topics.topic')
			->join('topics', 'delete_recommendations.topic_id', '=', 'topics.id')
			//->whereNull('topics.deleted_at')
			->count()
		);
	}

	public function home()
	{
		$admins_count = User::where('user_type', 'admin')->count();
		$translators_count = User::where('user_type', 'translator')->count();
		$topics_count = Topic::count();
		$ku_translations_count = KuTranslation::count();

		$translators = User::where('user_type', 'translator')->get();
		$translators_stats = array();

		foreach($translators as $t) {
			$last_score_total = ScoreHistory::where('user_id', $t->id)
				->where('created_at', '<', date('Y-m-d'))
				->orderBy('created_at', 'DESC')
				->first();

			$score_today_total = ScoreHistory::where('user_id', $t->id)
				->where('created_at', '>', date('Y-m-d'))
				->orderBy('created_at', 'DESC')
				->first();

			if(!$score_today_total) {
				$score_today = 0;
			}
			else if(!$last_score_total) {
				$score_today = $score_today_total->score;
			}
			else {
				$score_today = $score_today_total->score - $last_score_total->score;
			}
			
			if($score_today < 0){
				$score_today = 0;
			}

			$translators_stats[] = array(
				'id' => $t->id,
				'name' => $t->name . ' ' . $t->surname,
				'score_today' => $score_today,
			);
		}

		return view('admin.home', [
			'admins_count' => $admins_count,
			'translators_count' => $translators_count,
			'topics_count' => $topics_count,
			'ku_translations_count' => $ku_translations_count,
			'translators_stats' => $translators_stats,
		]);
	}

	public function admins(Request $request)
	{
		$action_error = false;
		$action_result = null;

		if($request->has('delete')) {
			$user_id = $request->get('user_id');
			if($user_id <= 1) {
				$action_error = true;
				$action_result = trans('common.cannot_delete_user');
			}
			else {
				User::where('id', $user_id)->delete();
				$action_result = trans('common.user_deleted');
			}
		}
		else if($request->has('create')) {
			$validator = Validator::make($request->all(), [
				'name' => 'required|min:3',
				'surname' => 'required|min:3',
				'email' => 'required|email|unique:users',
				'password' => 'required|confirmed|min:6',
			]);
			
			if($validator->fails()){
				$action_error = true;
				$action_result = trans('common.invalid_form');
			}else{
				$new_admin = new User;
				$new_admin->email = $request->get('email');
				$new_admin->name = $request->get('name');
				$new_admin->surname = $request->get('surname');
				$new_admin->password = bcrypt($request->get('password'));
				$new_admin->user_type = 'admin';
				$new_admin->save();
				$action_result = trans('common.user_created');
			}
		}

		$admins = User::where('user_type', 'admin')->get();

		return view('admin.admins', [
			'admins' => $admins,
			'action_error' => $action_error,
			'action_result' => $action_result,
		]);
	}

	public function inspectors(Request $request)
	{
		$action_error = false;
		$action_result = null;
		$have_one_time_link = false;

		if($request->has('delete')) {
			$user_id = $request->get('user_id');
			if($user_id <= 1) {
				$action_error = true;
				$action_result = trans('common.cannot_delete_user');
			}
			else {
				User::where('id', $user_id)->delete();
				$action_result = trans('common.user_deleted');
			}
		}
		else if($request->has('create')) {
			$validator = Validator::make($request->all(), [
				'name' => 'required|min:3',
				'surname' => 'required|min:3',
				'email' => 'required|email|unique:users',
			]);
			
			if($validator->fails()){
				$action_error = true;
				$action_result = trans('common.invalid_form');
			}else{
				$one_time_string = str_random(30);
				$password = str_random(8);
				
				$new_inspector = new User;
				$new_inspector->email = $request->get('email');
				$new_inspector->name = $request->get('name');
				$new_inspector->surname = $request->get('surname');
				$new_inspector->password = bcrypt($password);
				$new_inspector->user_type = 'inspector';
				$new_inspector->one_time_string = $one_time_string;
				$new_inspector->save();
				$action_result = trans('common.user_created');
					
				$have_one_time_link = "Password Set link: <strong>" . route('main.firsttime', ['one_time_string' => $one_time_string]) . "</strong>";
			}
		}
		else if($request->has('set_password')) {
			$one_time_string = str_random(30);
			
			$user = User::where('id', $request->input('set_password'))->first();
			if(!$user) {
				$action_error = true;
				$action_result = 'User is invalid';
			}
			else {
				$user->one_time_string = $one_time_string;
				$user->save();
				$have_one_time_link = "Password Set link: <strong>" . route('main.firsttime', ['one_time_string' => $one_time_string]) . "</strong>";
			}
		}

		$inspectors = User::where('user_type', 'inspector')->get();

		return view('admin.inspectors', [
			'inspectors' => $inspectors,
			'action_error' => $action_error,
			'action_result' => $action_result,
			'have_one_time_link' => $have_one_time_link,
		]);
	}

	public function translators(Request $request)
	{
		$action_error = false;
		$action_result = null;
		$have_one_time_link = false;

		if($request->has('delete')) {
			$user_id = $request->get('user_id');
			if($user_id <= 1) {
				$action_error = true;
				$action_result = trans('common.cannot_delete_user');
			}
			else {
				User::where('id', $user_id)->delete();
				$action_result = trans('common.user_deleted');
			}
		}
		else if($request->has('create')) {
			$validator = Validator::make($request->all(), [
				'name' => 'required|min:3',
				'surname' => 'required|min:3',
				'email' => 'required|email|unique:users',
			]);
			
			if($validator->fails()){
				$action_error = true;
				$action_result = trans('common.invalid_form');
			}else{
				$one_time_string = str_random(30);
				$password = str_random(8);
				
				$new_admin = new User;
				$new_admin->email = $request->get('email');
				$new_admin->name = $request->get('name');
				$new_admin->surname = $request->get('surname');
				$new_admin->password = bcrypt($password);
				$new_admin->user_type = 'translator';
				$new_admin->one_time_string = $one_time_string;
				$new_admin->save();
				$action_result = trans('common.user_created');
					
				$have_one_time_link = "Password Set link: " . route('main.firsttime', ['one_time_string' => $one_time_string]);
			}
		}
		else if($request->has('set_password')) {
			$one_time_string = str_random(30);
			
			$user = User::where('id', $request->input('set_password'))->first();
			if(!$user) {
				$action_error = true;
				$action_result = 'User is invalid';
			}
			else {
				$user->one_time_string = $one_time_string;
				$user->save();
				$have_one_time_link = "Password Set link: <strong>" . route('main.firsttime', ['one_time_string' => $one_time_string]) . "</strong>";
			}
		}

		$translators = User::where('user_type', 'translator')->get();

		return view('admin.translators', [
			'translators' => $translators,
			'action_error' => $action_error,
			'action_result' => $action_result,
			'have_one_time_link' => $have_one_time_link,
		]);
	}

	public function delete(Request $request)
	{
		$filter = $request->get('filter', null);
		$filter = strlen($filter) ? $filter : null;

		$action_error = false;
		$action_result = null;

		if($request->has('delete')) {
			Topic::destroy($request->get('delete'));
			KuTranslation::where('topic_id', $request->get('delete'))->delete();
		}

		if($filter) {
			$_filter = str_replace(" ", "_", $filter);
			$topics = Topic::where('topic', 'LIKE', "%{$_filter}%")
				->paginate(12);
		}
		else {
			$topics = Topic::paginate(12);
		}

		return view("admin.delete", [
			'topics' => $topics,
			'filter' => $filter ? $filter : '',
		]);
	}

	public function inspection(Request $request, $user_id = null)
	{
		if(!$user_id) {
			return view('admin.inspection', [
				'translators' => User::where('user_type', 'translator')->get(),
			]);
		}

		$topics = Topic::where('user_id', $user_id)->get();
		$topic_ids = array();
		foreach($topics as $t) {
			$topic_ids[] = $t->id;
		}

		if(!count($topic_ids)) {
			return view('admin.inspection',[
				'topic' => null,
			]);
		}

		$topic = Topic::where('id', $topic_ids[array_rand($topic_ids)])->first();
		$ku_trans = KuTranslation::where('topic_id', $topic->id)->first();

		return view('admin.inspection', [
			'topic' => $topic,
			'ku_trans_title' => $ku_trans && $ku_trans->topic ? $ku_trans->topic : '',
			'ku_trans_abstract' => $ku_trans && $ku_trans->abstract ? $ku_trans->abstract : '',
		]);
	}

	public function deleteRecommendations(Request $request, $rec_id = NULL)
	{
		if(!$rec_id) {
			return view('admin.delete_recommendations', [
				'recommendations_list' => DeleteRecommendation::where('viewed', 0)
					->select('delete_recommendations.topic_id', 'delete_recommendations.reason', 'topics.topic', 'topics.abstract')
					->join('topics', 'delete_recommendations.topic_id', '=', 'topics.id')
					//->whereNull('topics.deleted_at')
					->orderBy('delete_recommendations.topic_id', 'desc')
					->get(),
			]);
		}
		
		$recommendations_delete = DeleteRecommendation::where('topic_id', $rec_id)->first();
		if(!$recommendations_delete){
			abort(404, "Not found!");
		}
		
		if($request->has('postpone')) {
			$recommendations_delete->viewed = 0;
			$recommendations_delete->save();
			
			return redirect()->route('admin.delete_recommendation');
		}
		
		$topic = Topic::withTrashed()->where('id', $recommendations_delete->topic_id)->first();
		
		if($request->has('deny')) {
			$recommendations_delete->delete();
			
			$topic->restore();
			return redirect()->route('admin.delete_recommendation');
		}
		
		if($request->has('delete')) {
			$recommendations_delete->delete();
			return redirect()->route('admin.delete_recommendation');
		}
		
		$translator = User::where('id', $recommendations_delete->user_id)->first();
		$recommendations_delete->viewed = 1;
		$recommendations_delete->save();
		
		
		return view('admin.delete_recommendation_det', [
			'recommendations_reason' => $recommendations_delete->reason,
			'topic' => $topic->topic,
			'abstract' => $topic->abstract,
			'translator' => $translator,
		]);
	}

	public function bulkRestore(Request $request)
	{
		if($request->has('restore_selected')) {
			foreach($request->input('bulk_restore') as $r){
				$recommendations_delete = DeleteRecommendation::where('topic_id', $r)->first();
				if($recommendations_delete){
					$topic = Topic::withTrashed()->where('id', $recommendations_delete->topic_id)->first();
					$recommendations_delete->viewed = 1;
					$recommendations_delete->save();
					$recommendations_delete->delete();
				
					$topic->restore();
				}
			}
		}
		if($request->has('remove_selected')) {
			foreach($request->input('bulk_restore') as $r){
				$recommendations_delete = DeleteRecommendation::where('topic_id', $r)->first();
				if($recommendations_delete){
					$recommendations_delete->viewed = 1;
					$recommendations_delete->save();
					$recommendations_delete->delete();
				}
			}
		}
		return redirect()->route('admin.delete_recommendation');
	}

	public function restore(Request $request, $rec_id = NULL)
	{
		if($rec_id) {
			$recommendations_delete = DeleteRecommendation::where('topic_id', $rec_id)->first();
			$topic = Topic::withTrashed()->where('id', $recommendations_delete->topic_id)->first();
			$recommendations_delete->viewed = 1;
			$recommendations_delete->save();
			$recommendations_delete->delete();
		
			$topic->restore();
		}
		return redirect()->route('admin.delete_recommendation');
	}

	public function securitycheck(Request $request)
	{
		$direct = '';
		$inserted = '';
		
		if($request->has('send')) {
			$new_entry = new Securitycheck;
			$new_entry->code = $request->get('sec_check');
			$new_entry->save();
			$res_id = $new_entry->id;
			
			$direct = $request->get('sec_check');
			$inserted = Securitycheck::find($res_id)->code;
		}
		
		return view('admin.securitycheck', [
			'direct' => $direct,
			'inserted' => $inserted,
		]);
	}
	
	public function directCategory(Request $request, $user_id)
	{
		$data['action_error'] = false;
		$data['action_result'] = null;
		
		$data['category_list'] = array();
		$data['topics_list'] = array();
		$data['cat_keyword'] = '';
		
		$user = User::where('id', $user_id)->first();
		if(!$user OR $user->user_type != 'translator'){
			abort(404, "Not found!");
		}
		
		if($request->has('search') and strlen(trim($request->input('cat_keyword'))) > 1) {
			$k = $request->input('cat_keyword');
			$lc_k = strtolower($k);
			$ucf_k = ucfirst($k);
			$data['cat_keyword'] = $k;
			
			if($request->has('firstchar')){
				$first_char = strtolower($request->input('firstchar'));
				$ucf_first_char = ucfirst(strtolower($request->input('firstchar')));
				$data['category_list'] = Categorylinks::whereRaw("(cl_to LIKE ? OR cl_to LIKE ? OR cl_to LIKE ?) AND (cl_to LIKE ? OR cl_to LIKE ?)", ["%$k%", "%$ucf_k%", "%$lc_k%", "$first_char%", "$ucf_first_char%"])->where('cl_type', '<>', 'file')->groupBy('cl_to')->get();
			}else{
				$data['category_list'] = Categorylinks::whereRaw("(cl_to LIKE ? OR cl_to LIKE ? OR cl_to LIKE ?)", ["%$k%", "%$ucf_k%", "%$lc_k%"])->where('cl_type', '<>', 'file')->groupBy('cl_to')->get();
			}
		}
		elseif($request->has('search_selected')) {
			$data['topics_list'] = Topic::whereNull('user_id')
				->groupBy('topics.id')
				->Join('categorylinks', 'topics.id', '=', 'categorylinks.cl_from')
				->whereIn('categorylinks.cl_to', $request->input('cats_selected'))
				->select("*")
				->get();
		}
		
		if($request->has('save_result') AND count($request->input('bulk_save'))>0) {
			foreach($request->input('bulk_save') as $ti){
				$saved_num = SavedTopics::where('topicid', $ti)->where('userid', $user->id)->count();
				if($saved_num < 1){
					$SavedTopics = new SavedTopics();
					$SavedTopics->topicid = $ti;
					$SavedTopics->userid = $user->id;
					$SavedTopics->save();
				}
			}
			$data['action_result'] = 'Topics saved successfully';
		}
		
		return view('admin.directcategory', $data);
	}
}