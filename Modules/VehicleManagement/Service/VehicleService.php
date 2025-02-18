<?php

namespace Modules\VehicleManagement\Service;

use App\Service\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\VehicleManagement\Repository\VehicleRepositoryInterface;
use Modules\VehicleManagement\Service\Interface\VehicleServiceInterface;

class VehicleService extends BaseService implements VehicleServiceInterface
{
    protected $vehicleRepository;

    public function __construct(VehicleRepositoryInterface $vehicleRepository)
    {
        parent::__construct($vehicleRepository);
        $this->vehicleRepository = $vehicleRepository;
    }


    public function index(array $criteria = [], array $relations = [], array $whereHasRelations = [], array $orderBy = [], int $limit = null, int $offset = null, array $withCountQuery = [], array $appends = [], array $groupBy = []): Collection|LengthAwarePaginator
    {
        $data = [];
        if (array_key_exists('status', $criteria) && $criteria['status'] !== 'all') {
            $data['is_active'] = $criteria['status'] == 'active' ? 1 : 0;
        }
        $searchData = [];
        if (array_key_exists('search', $criteria) && $criteria['search'] != '') {
            $searchData['fields'] = ['licence_plate_number', 'vin_number'];
            $searchData['value'] = $criteria['search'];
        }
        $whereInCriteria = [];
        $whereBetweenCriteria = [];
        $whereHasRelations = [];
        return $this->baseRepository->getBy(criteria: $data, searchCriteria: $searchData, whereInCriteria: $whereInCriteria, whereBetweenCriteria: $whereBetweenCriteria, whereHasRelations: $whereHasRelations, relations: $relations, orderBy: $orderBy, limit: $limit, offset: $offset, withCountQuery: $withCountQuery);
    }

    public function create(array $data): ?Model
    {
        $documents = [];
        if (array_key_exists('other_documents', $data)) {
            foreach ($data['other_documents'] as $doc) {
                $extension = $doc->getClientOriginalExtension();
                $documents[] = fileUploader('vehicle/document/', $extension, $doc);
            }
        }
        $storeData = [
            'brand_id' => $data['brand_id'],
            'model_id' => $data['model_id'],
            'category_id' => $data['category_id'],
            'licence_plate_number' => $data['licence_plate_number'],
            'licence_expire_date' => $data['licence_expire_date'],
            'vin_number' => $data['vin_number'],
            'transmission' => $data['transmission'],
            'parcel_weight_capacity' => $data['parcel_weight_capacity'],
            'fuel_type' => $data['fuel_type'],
            'ownership' => $data['ownership'],
            'driver_id' => $data['driver_id'],
            'documents' => $documents,
        ];
        return $this->vehicleRepository->create($storeData);
    }

    public function update(int|string $id, array $data = []): ?Model
    {
        $updateData = [
            'brand_id' => $data['brand_id'],
            'model_id' => $data['model_id'],
            'category_id' => $data['category_id'],
            'licence_plate_number' => $data['licence_plate_number'],
            'licence_expire_date' => $data['licence_expire_date'],
            'vin_number' => $data['vin_number'],
            'transmission' => $data['transmission'],
            'parcel_weight_capacity' => $data['parcel_weight_capacity'],
            'fuel_type' => $data['fuel_type'],
            'ownership' => $data['ownership'],
            'driver_id' => $data['driver_id'],
        ];


        $existingDocuments = array_key_exists('existing_documents', $data) ? $data['existing_documents'] : [];
        $deletedDocuments = array_key_exists('deleted_documents', $data) ? explode(',', $data['deleted_documents']) : [];

        // Remove deleted documents from the existing list
        $documents = array_diff($existingDocuments, $deletedDocuments);

        // Handle new uploads
        if ($data['other_documents'] ?? null) {
            foreach ($data['other_documents'] as $doc) {
                $extension = $doc->getClientOriginalExtension();
                $documents[] = fileUploader('vehicle/document/', $extension, $doc);
            }
        }
        $updateData['documents'] = $documents;
        return $this->vehicleRepository->update($id, $updateData);
    }

    public function export(array $criteria = [], array $relations = [], array $orderBy = [], int $limit = null, int $offset = null, array $withCountQuery = []): Collection|LengthAwarePaginator|\Illuminate\Support\Collection
    {
        return $this->index(criteria: $criteria, relations: $relations, orderBy: $orderBy)->map(function ($item) {
            return [
                'Id' => $item['id'],
                'Driver Name' => $item?->driver?->full_name ?? $item?->driver?->first_name . ' ' . $item?->driver?->last_name,
                'Type' => ucwords(str_replace('_', ' ', $item?->category?->type ?? 'N/A')),
                'Brand' => $item->brand->name,
                'Model' => $item->model->name,
                'License' => $item['licence_plate_number'],
                'Owner' => ucwords($item['ownership']),
                'Seat Capacity' => $item->model->seat_capacity,
                "Hatch Bag Capacity" => $item->model->hatch_bag_capacity,
                "Fuel" => ucwords($item['fuel_type']),
                "Mileage" => $item->model->engine,
                'Status' => $item['is_active'] == 1 ? "Active" : "Inactive",
            ];
        });
    }

}
