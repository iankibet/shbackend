<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\Contribution;
use App\Models\Core\Member;
use App\Models\User;
use Iankibet\Shbackend\App\Repositories\GraphStatsRepository;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    //
    public function getDashboardStats() {
        $start = Carbon::createFromFormat('m-d-Y',\request('from'))->startOfDay();
        $end = Carbon::createFromFormat('m-d-Y',\request('to'))->endOfDay();
        $totalContributed = Contribution::whereBetween('date',[$start, $end])
            ->where('contributions.status',getContributionStatus('confirmed'))
            ->sum('amount');
        $graphData = GraphStatsRepository::getDrawables($start,$end,Contribution::where('status',getContributionStatus('confirmed')),['amount'],2, 'contributions', 'date');
        $total = Contribution::where('contributions.status',getContributionStatus('confirmed'))->whereBetween('date',[$start, $end])->count();
        $membersParticipated = Contribution::whereBetween('date',[$start, $end])->where('contributions.status',getContributionStatus('confirmed'))->distinct()->count('member_id');
        return [
            'totalContributed'=>$totalContributed,
            'graph'=>$graphData,
            'contributionsCount'=>$total,
            'membersParticipated'=>$membersParticipated
        ];
    }
    public function getTopMembers($from,$to){
        $start = Carbon::createFromFormat('m-d-Y',$from)->startOfDay();
        $end = Carbon::createFromFormat('m-d-Y',$to)->endOfDay();
        $topAmounts = Member::join('contributions','contributions.member_id','=','members.id')
            ->whereBetween('contributions.date',[$start, $end])
            ->where('contributions.status',getContributionStatus('confirmed'))
            ->groupBy('contributions.member_id')
            ->select('contributions.member_id', DB::raw('sum(contributions.amount) as amount'))
            ->orderByRaw('SUM(amount) desc');
        if(!\request('export')) {
            $topAmounts = $topAmounts->limit(10)->paginate();
        } else{
            $topAmounts = $topAmounts->get();
        }
        $memberIds = Member::join('contributions','contributions.member_id','=','members.id')
            ->whereBetween('contributions.date',[$start, $end])
            ->where('contributions.status',getContributionStatus('confirmed'))
            ->groupBy('contributions.member_id')
            ->select('contributions.member_id')->pluck('contributions.member_id')->toArray();
        $members = User::whereIn('id',$memberIds)->get()->keyBy('id')->toArray();
        return [
            'status'=>'success',
            'data'=>SearchRepo::of($topAmounts,'contributions',['user_id'])
                ->addcolumn('name', function ($amount) use ($members) {
                    if(isset($members[$amount->member_id])) {
                        return $members[$amount->member_id]['name'];
                    }
                    return Member::find($amount->member_id)->name;
                })
                ->addcolumn('member_id',function($amount){
                    return str_pad($amount->member_id,4,0,STR_PAD_LEFT);
                })
                ->make(true)
        ];
    }
}
