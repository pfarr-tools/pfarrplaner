<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App\Events\AbsenceBeforeDelete;
use App\Events\AbsenceUpdated;
use App\Http\Requests\AbsenceRequest;
use App\Models\Attachment;
use App\Models\Leave\Absence;
use App\Models\Leave\Poolmaster;
use App\Models\Leave\Replacement;
use App\Models\People\User;
use App\Models\Service;
use App\Services\CalendarService;
use App\Traits\HandlesAttachmentsTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Class AbsenceController
 * @package App\Http\Controllers
 */
class AbsenceController extends Controller
{
    use HandlesAttachmentsTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the absence planner
     *
     * @return Response
     */
    public function index(Request $request, $year = 0, $month = 0)
    {
        if (false !== ($r = $this->redirectIfMissingParameters($request, 'absences.index', $year, $month))) {
            return $r;
        }

        $start = CalendarService::getStartOfPeriod($year, $month);
        $days = Absence::getDaysForPlanner($start->copy());
        $yearExpression = DB::connection()->getDriverName() == 'sqlite'
            ? "strftime('%Y', \"absences\".\"from\")"
            : 'YEAR(absences.from)';
        $years = Absence::select(DB::raw($yearExpression.' as year'))->distinct()->get()->pluck('year')->sort();
        $pinList = $request->user()->getSetting('planner_pinned_users', []);
        $sectionConfig = $request->user()->getSetting('planner_open_sections', null);

        $pools = Auth::user()->pools;

        return Inertia::render('Absences/Planner',
                               compact('start', 'days', 'year', 'month', 'years', 'pinList', 'sectionConfig', 'pools'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(Request $request, $year, $month, User $user, $day = 1)
    {
        $absence = new Absence([
            'user_id' => $user->id,
            'workflow_status' => Absence::STATUS_NEW,
        ]);
        $absence->setRelation('user', $user);

        Gate::authorize('create', $absence);

        [$from, $to] = $this->resolveCreateRange($request, (int)$year, (int)$month, (int)$day);
        $workflowStatus = Absence::STATUS_NEW;
        if ((Auth::user()->id == $user->id) && Auth::user()->can('selfAdminister', $absence)) {
            $workflowStatus = Absence::STATUS_SELF_ADMINISTERED;
        }

        $absence = Absence::create([
            'user_id' => $user->id,
            'reason' => 'Urlaub',
            'from' => $from,
            'to' => $to,
            'workflow_status' => $workflowStatus,
            'sick_days' => false,
            'replacement_notes' => '',
            'internal_notes' => '',
            'admin_notes' => '',
            'approver_notes' => '',
        ]);

        return redirect()->route('absence.edit', $absence->id);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AbsenceRequest $request
     * @return JsonResponse|RedirectResponse
     */
    public function store(AbsenceRequest $request)
    {
        $absence = Absence::create($request->validated());
        $absence->setupReplacements($request->user(), $request->get('replacements') ?: []);

        event(new AbsenceUpdated($absence));

        if ($request->get('noRedirect', false)) {
            $absence->refresh();
            $absence->load(['replacements', 'attachments', 'user', 'checkedBy', 'approvedBy']);
            return response()->json([
                'absence' => $absence,
                'editUrl' => route('absence.edit', $absence->id),
            ]);
        }

        if ($url = $request->get('redirectTo', false)) {
            return Inertia::location($url);
        }

        return redirect()->route('absence.edit', $absence->id);
    }


    /**
     * Get user data for absence planner (api)
     * @return \Illuminate\Http\JsonResponse
     */
    public function users()
    {
        $users = Auth::user()->getViewableAbsenceUsers()->each->load('pools');
        foreach ($users as $key => $user) {
            $user->canEdit = false;
            if (($user->id == Auth::user()->id)
                || (Auth::user()->hasPermissionTo('fremden-urlaub-bearbeiten')
                    && (!$user->hasRole('Pfarrer:in'))
                    && (count(Auth::user()->writableCities->intersect($user->homeCities))))
            ) {
                $user->canEdit = true;
            }
        }
        return response()->json($users);
    }

    /**
     * Get displayable days for absence planner (api)
     * @param $start Start date
     * @param User $user User to be displayed
     * @return \Illuminate\Http\JsonResponse
     */
    public function days($start, User $user)
    {
        $start = CalendarService::getStartOfPeriod($start);
        $end = $start->copy()->addMonth(1)->subDay(1);
        $plannerStart = $this->plannerDay($start);
        $plannerEnd = $this->plannerDay($end);
        $days = Absence::getDaysForPlanner($plannerStart->copy());

        $absences = Absence::where('user_id', $user->id)
            ->where('to', '>=', $start)
            ->where('from', '<=', $end)
            ->get();

        $poolmasters = Poolmaster::with('pool')
            ->where('user_id', $user->id)
            ->where('start', '<=', $end)
            ->where('end', '>=', $start)
            ->get();

        // Find out whether current user is a replacement for this absence
        if ($user->id != Auth::user()->id) {
            foreach ($absences as $absence) {
                $absence->replacing = false;
                $absence->canEdit = Auth::user()->can('update', $absence);
                /** @var Replacement $replacement */
                foreach ($absence->replacements as $replacement) {
                    if ($replacement->users->pluck('id')->contains(Auth::user()->id)) {
                        $absence->replacing = true;
                    }
                }
            }
        }
        $realAbsences = $absences->values();

        // add poolmaster "absences"
        foreach ($poolmasters as $poolmaster) {
            foreach ($this->buildPoolmasterPlannerAbsences($poolmaster, $user, $realAbsences) as $absence) {
                $absences->push($absence);
            }
        }

        foreach ($days as $index => $day) {
            $days[$index]['services'] = Service::atDate($days[$index]['date'])
                ->userParticipates($user)
                ->count();
            $days[$index]['busy'] = ($days[$index]['services'] > 0);
            $days[$index]['absent'] = false;
            $days[$index]['absence'] = null;
            $days[$index]['duration'] = 0;
            $days[$index]['show'] = true;
        }


        foreach ($absences as $absence) {
            $absenceStart = $this->plannerDay($absence->from);
            $absenceEnd = $this->plannerDay($absence->to);
            $index = ($absenceStart < $plannerStart ? 1 : $absenceStart->day);
            $visibleStart = $absenceStart->copy()->max($plannerStart->copy());
            $visibleEnd = $absenceEnd->copy()->min($plannerEnd->copy());
            $days[$index]['absence'] = $absence;
            $days[$index]['duration'] = $visibleStart->diffInDays($visibleEnd) + 1;
            $endIndex = ($absenceEnd > $plannerEnd ? $plannerEnd->day : $absenceEnd->day);
            for ($i = $index; $i <= $endIndex; $i++) {
                $days[$i]['absent'] = true;
                if ($i > $index) {
                    $days[$i]['show'] = false;
                }
            }
        }

        return response()->json($days);
    }

    /**
     * @param Poolmaster $poolmaster
     * @param User $user
     * @param \Illuminate\Support\Collection<int, Absence> $realAbsences
     * @return array<int, Absence>
     */
    protected function buildPoolmasterPlannerAbsences(Poolmaster $poolmaster, User $user, $realAbsences): array
    {
        $segments = [[
            'from' => $this->plannerDay($poolmaster->start),
            'to' => $this->plannerDay($poolmaster->end),
        ]];

        foreach ($realAbsences as $realAbsence) {
            $absenceStart = $this->plannerDay($realAbsence->from);
            $absenceEnd = $this->plannerDay($realAbsence->to);
            $updatedSegments = [];

            foreach ($segments as $segment) {
                if (($absenceEnd < $segment['from']) || ($absenceStart > $segment['to'])) {
                    $updatedSegments[] = $segment;
                    continue;
                }

                if ($absenceStart > $segment['from']) {
                    $updatedSegments[] = [
                        'from' => $segment['from']->copy(),
                        'to' => $absenceStart->copy()->subDay(),
                    ];
                }

                if ($absenceEnd < $segment['to']) {
                    $updatedSegments[] = [
                        'from' => $absenceEnd->copy()->addDay(),
                        'to' => $segment['to']->copy(),
                    ];
                }
            }

            $segments = $updatedSegments;
            if (!$segments) {
                break;
            }
        }

        return array_map(function (array $segment) use ($poolmaster, $user) {
            $absence = new Absence([
                'reason' => 'Poolmaster:in für "'.$poolmaster->pool->name.'"',
                'from' => $segment['from']->copy(),
                'to' => $segment['to']->copy(),
                'user_id' => $user->id,
            ]);
            $absence->poolmaster = true;
            $absence->poolmaster_id = $poolmaster->id;
            $absence->user = $user;
            $absence->canEdit = Auth::user()->can('update', $poolmaster);

            return $absence;
        }, $segments);
    }

    /**
     * Normalize a stored timestamp to the rendered Berlin calendar day.
     *
     * @param mixed $value
     * @return Carbon
     */
    protected function plannerDay($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone('Europe/Berlin')->startOfDay();
        }

        return Carbon::parse($value)->setTimezone('Europe/Berlin')->startOfDay();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Absence $absence
     * @return \Inertia\Response
     */
    public function edit(Request $request, Absence $absence)
    {
        return $this->renderEditor($absence, $request);
    }

    /**
     * Render the absence editor.
     *
     * @param Absence $absence
     * @param Request $request
     * @return \Inertia\Response
     */
    protected function renderEditor(Absence $absence, Request $request)
    {
        $absence->load(['replacements', 'checkedBy', 'approvedBy']);
        $absenceUser = $absence->user ?: User::findOrFail($absence->user_id);
        $absence->setRelation('user', $absenceUser);
        $absenceUser->load(['vacationAdmins', 'vacationApprovers', 'cities', 'pools']);

        $mayCheck = $absenceUser->vacationAdmins->pluck('id')->contains(Auth::user()->id);
        $mayApprove = $absenceUser->vacationApprovers->pluck('id')->contains(Auth::user()->id);
        $maySelfAdminister = Auth::user()->can('selfAdminister', $absence);


        $year = $month = null;
        if ($request->has('startMonth')) {
            list($month, $year) = explode('-', $request->get('startMonth'));
        }
        if (!$year) {
            $year = date('Y');
        }
        if (!$month) {
            $year = date('m');
        }
        $users = User::visibleFor(Auth::user())->get();

        return Inertia::render(
            'Absences/AbsenceEditor',
            compact('absence', 'month', 'year', 'users', 'mayCheck', 'mayApprove', 'maySelfAdminister')
        );
    }

    /**
     * @param Request $request
     * @param int $year
     * @param int $month
     * @param int $day
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveCreateRange(Request $request, int $year, int $month, int $day): array
    {
        $fromDate = $request->query('fromDate');
        $toDate = $request->query('toDate');

        if ($fromDate && $toDate) {
            return [
                Carbon::createFromFormat('Y-m-d', $fromDate, 'Europe/Berlin')->startOfDay()->setTimezone('UTC'),
                Carbon::createFromFormat('Y-m-d', $toDate, 'Europe/Berlin')->endOfDay()->setTimezone('UTC'),
            ];
        }

        $date = Carbon::create($year, $month, $day, 0, 0, 0, 'Europe/Berlin');

        return [
            $date->copy()->startOfDay()->setTimezone('UTC'),
            $date->copy()->endOfDay()->setTimezone('UTC'),
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Absence $absence
     * @return JsonResponse|RedirectResponse
     */
    public function update(AbsenceRequest $request, Absence $absence)
    {
        $absence->update($request->validated());
        $absence->setupReplacements($request->user(), $request->get('replacements') ?: []);



        event(new AbsenceUpdated($absence));

        if ($request->get('noRedirect', false)) return response()->json();
        if ($url = $request->get('redirectTo', false)) {
            return Inertia::location($url);
        }
        return redirect()->route(
            'absences.index',
            ['month' => $absence->from->format('m'), 'year' => $absence->from->format('Y')]
        );
    }

    /**
     * Remove the specified resource from storage. Send rejection notice if necessary.
     *
     * @param Absence $absence
     * @return Response
     */
    public function destroy(Request $request, Absence $absence)
    {
        event(new AbsenceBeforeDelete($absence));

        if ($request->get('sendRejectionMail', false)) {
            $recipients = collect([$absence->user]);
            $recipients = $recipients->merge($absence->user->vacationAdmins);
            if ($absence->workflow_status > 0) {
                $recipients = $recipients->merge($absence->user->vacationApprovers);
            }
            // filter out users without email address
            $recipients->reject(function ($user) { return empty($user->email); });
            Mail::to($recipients)->send(new \App\Mail\Absence\AbsenceRejected($absence, Auth::user()));
        }

        $absence->delete();
        return redirect()->route(
            'absences.index',
            [
                'month' => $request->get('month', Carbon::now()->month),
                'year' => $request->get('year', Carbon::now()->year)
            ]
        );
    }

    /**
     * @param Request $request
     * @param $route
     * @param $year
     * @param $month
     * @return bool|RedirectResponse
     */
    protected function redirectIfMissingParameters(Request $request, $route, $year, $month)
    {
        $defaultMonth = Carbon::now()->month;
        $defaultYear = Carbon::now()->year;

        $initialYear = $year;
        $initialMonth = $month;


        if ($month == 13) {
            $year++;
            $month = 1;
        }
        if (($year > 0) && ($month == 0)) {
            $year--;
            $month = 12;
        }

        if ((!$year) || (!$month) || (!is_numeric($month)) || (!is_numeric($year)) || (!checkdate($month, 1, $year))) {
            $year = $defaultYear;
            $month = $defaultMonth;
        }

        if (($year == $initialYear) && ($month == $initialMonth)) {
            return false;
        }

        $data = compact('month', 'year');
        $slave = $request->get('slave', 0);
        if ($slave) {
            $data = array_merge($data, compact('slave'));
        }

        return redirect()->route($route, $data);
    }


    /**
     * @param Request $request
     * @param Absence $absence
     * @return \Illuminate\Http\JsonResponse
     */
    public function attach(Request $request, Absence $absence)
    {
        Gate::authorize('update', $absence);
        $this->handleAttachments($request, $absence);
        $absence->refresh();
        return response()->json($absence->attachments);
    }

    /**
     * @param Request $request
     * @param Absence $absence
     * @param Attachment $attachment
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function detach(Request $request, Absence $absence, Attachment $attachment)
    {
        Gate::authorize('update', $absence);
        $attachment = $absence->attachments()->findOrFail($attachment->id);
        $file = $attachment->file;
        $absence->attachments()->where('id', $attachment->id)->delete();
        Storage::delete($file);
        $attachment->delete();
        $absence->refresh();
        return response()->json($absence->attachments);
    }


}
