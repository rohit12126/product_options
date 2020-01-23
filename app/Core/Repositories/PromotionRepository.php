<?php 

namespace App\Core\Repositories;

use App\Core\Models\OrderCore\Promotion;
use App\Core\Models\OrderCore\Promotion\Tier;
use App\Core\Interfaces\PromotionInterface;
use App\Core\Repositories\BaseRepository;

class PromotionRepository extends BaseRepository implements PromotionInterface
{
	protected $model;

	protected $tierModel;

	public function __construct(Promotion $model,Tier $tierModel)
	{
		$this->model = $model;
		$this->tierModel = $tierModel;
	}

	public function getPromotionByCode($code)
	{
		return $this->model->where('code',$code)->first();
	}

	public function getPromotionTier($params = []){
		if(empty($params))
			return ;

		if(!empty($params['promotion_id']))
		{
			$this->tierModel->where('promotion_id',$params['promotion_id']);
		}

		if(!empty($params['level']))
		{
			$this->tierModel->where('level',$params['level']);
		}

		return $this->tierModel->get();
	}
} 