<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Admin;
use App\Models\StaticModel;

class AdminController extends Controller
{
    protected $view;
    protected $breadCrumbs;
    protected $titleMenu;

    public function __construct()
    {
        $this->view = 'admin.';
        $this->titleMenu = @Admin::getMenu()->label;
        $this->breadCrumbs=Admin::breadCrumbs();
        $this->user=auth()->user();
        view()->share('titleMenu',$this->titleMenu);
        view()->share('breadCrumbs',$this->breadCrumbs);
        view()->share('elfinderPath','packages/barryvdh/elfinder');
    }

    public function makeView($view,$data=[])
    {
        return view($this->view.$view,$data);
    }

    public function redirectAction($message,$action="",$session="")
    {
        $action = !empty($action) ? $action : 'index';
        return redirect(urlBackendAction($action))->with($session,$message);
    }

    public function redirectActionSuccess($message="",$action="")
    {
        $message=!empty($message) ? $message : "Data has been updated";

        return $this->redirectAction($message,$action,'success');
    }

    public function redirectActionInfo($message,$action="")
    {
        return $this->redirectAction($message,$action,'info');
    }

    public function redirectActionDanger($message,$action="")
    {
        return $this->redirectAction($message,$action,'danger');
    }

    public function create($model,array $data)
    {
        $model = $model->create($data);
        return $this->redirectActionSuccess('Data has been saved');
    }

    public function update($model,array $data)
    {
        $model = $model->update($data);
        return $this->redirectActionSuccess('Data has been updated');
    }

    public function delete($model,$images=[])
    {
        try
        {
            foreach($images as $image)
            {
                @unlink(Admin::publicContents($image));
            }

            $model->delete();

            return redirect(urlBackendAction('index'))

            ->withSuccess("Data has been deleted");

        }catch(\Exception $e){

            return redirect(urlBackendAction('index'))

            ->withSuccess("Data cannot be deleted");

        }
    }

    public function handleUploadStorage($request,$model,$fieldName,$resize=[])
    {
        $image = $this->handleUpload($request,$model,$fieldName,$resize);
        $path = asset('contents/'.$image);
        if(!empty($image))
        {
            \Storage::put('reza.jpg',public_path("contents/".$image));
        }
    }

    public function handleUpload($request,$model,$fieldName,$resize=[])
    {
        $hiddenName = "hidden_$fieldName";
        $image = $request->file($fieldName);
        if(!empty($image))
        {
            if(!empty($model->$fieldName))
            {
                @unlink(public_path('contents/'.$model->$fieldName));
            }

            $imageName = str_random(10).'.'.$image->getClientOriginalExtension();

            $image = \Image::make($image);

            if(!empty($resize))
            {
                $image = $image->resize($resize[0],$resize[1]);
            }

            $image = $image->save(public_path('contents/'.$imageName));

            return $imageName;

        }else{
            if(isset($request->{$hiddenName}))
            {
                return $model->$fieldName;
            }else{
                @unlink(public_path('contents/'.$model->$fieldName));
            }
        }
    }

    public function handleUploadStatic($request,$fieldName,$resize=[])
    {
        $hiddenName = "hidden_$fieldName";
        $str_old_image = "old_".$fieldName;
        $oldImage = $request->{$str_old_image};
        $image = $request->file($fieldName);
        if(!empty($image))
        {
            @unlink(public_path('contents/'.$oldImage));

            $imageName = str_random(10).'.'.$image->getClientOriginalExtension();

            $image = \Image::make($image);

            if(!empty($resize))
            {
                $image = $image->resize($resize[0],$resize[1]);
            }

            $image = $image->save(public_path('contents/'.$imageName));

            return $imageName;

        }else{
            if(isset($request->{$hiddenName}))
            {
               return $oldImage;
            }else{
                @unlink(public_path('contents/'.$oldImage));
            }
        }
    }

    public function publish_draft($model)
    {
        $message = "Data has been Published";
        $status = 'publish';
        if($model->status == 'publish')
        {
            $status = 'draft';
            $message = "Data has been Drafted";
        }

        $model->update([
            'status'=>$status
        ]);

        return redirect()->back()
            ->with('success',$message);
    }

    public function update_order($model)
    {
        $request = request();
        $count   = count($request->id);
        for ($a = 0; $a < $count; $a++) {
            $model->find($request->id[$a])
                ->update([
                    'order' => $a,
                ]);
        }
    }

    public function updateStaticPage($inputs,$group)
    {
        $this->model = new StaticModel();

        $byPass          = ['_token'];
        $data = [];
        foreach ($inputs as $key => $value) {
            if (!in_array($key, $byPass)) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                $data[] = [
                    'group' => $group,
                    'key'   => $key,
                    'value' => $value,
                ];
            }
        }

        $this->model->whereGroup($group)->delete();

        $this->model->insert($data);
    }

    public function handleUploadStaticResponsive($inputs,$name)
    {
        $data = [
            $name,
            $name.'_dekstop',
            $name.'_tablet',
            $name.'_mobile',
        ];

        $request = request();
        foreach($data as $row)
        {
            $upload = $this->handleUploadStatic($request,$row);
            if(!empty($upload))
            {
                $inputs[$row] = $upload;
            }
        }
        return $inputs;
    }


}
