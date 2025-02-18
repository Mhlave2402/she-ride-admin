<?php

namespace Modules\BusinessManagement\Http\Controllers\Web\Admin\Configuration;

use App\Http\Controllers\BaseController;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Modules\BusinessManagement\Http\Requests\SmsSettingSetupStoreOrUpdateRequest;
use Modules\BusinessManagement\Service\Interface\SettingServiceInterface;
use Modules\Gateways\Traits\Processor;

class SMSConfigController extends BaseController
{
    use Processor;

    protected $settingService;
    public function __construct(SettingServiceInterface $settingService)
    {
        parent::__construct($settingService);
        $this->settingService = $settingService;
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return parent::index($request, $type); // TODO: Change the autogenerated stub
    }

    public function smsConfigGet(): Application|Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
    {
        $dataValues = $this->settingService->getBy(criteria: ['settings_type' => SMS_CONFIG]);
        return view('businessmanagement::admin.configuration.sms-gateway', compact('dataValues'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return RedirectResponse
     */
    public function smsConfigSet(SmsSettingSetupStoreOrUpdateRequest $request): RedirectResponse
    {
        $this->settingService->storeOrUpdateSMSSetting($request->validated());
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }
}
