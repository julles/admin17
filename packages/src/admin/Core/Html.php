<?php namespace Admin\Core;

use App\Models\Permission;

class Html
{
    public function __construct()
    {
        $this->permission = new Permission();
    }

    public function arrayToString($arr = [])
    {
        $str = "";
        foreach ($arr as $key => $val) {
            $str .= "$key = '$val'";
        }
        return $str;
    }

    public function href($value, $attributes = [])
    {
        $resultAttributes = $this->arrayToString($attributes);

        $str = "<a $resultAttributes>" . $value . "</a>";

        return $str;
    }

    public function linkCreate($values = "")
    {
        $attributes = [
            'class' => 'btn btn-primary',
            'href'  => urlBackendAction('create'),
        ];
        $values     = !empty($values) ? $values : 'Add New';
        $permission = $this->permission->roleHasPermissionThisMethod("", 'create');

        return $permission == 'method_found' ? $this->href($values, $attributes) : null;
    }

    public function linkDelete($id)
    {
        $attributes = [
            'class'   => 'btn btn-danger btn-sm',
            'href'    => urlBackendAction('delete/' . $id),
            'onclick' => 'return confirm("Are you sure want to delete this item ?")',
        ];

        return $this->href('Delete', $attributes);
    }

    public function linkUpdate($id)
    {
        $attributes = [
            'class' => 'btn btn-success btn-sm',
            'href'  => urlBackendAction('update/' . $id),
        ];

        return $this->href('Edit', $attributes);
    }

    public function linkView($id)
    {
        $attributes = [
            'class' => 'btn btn-default btn-sm',
            'href'  => urlBackendAction('view/' . $id),
        ];

        return $this->href('View', $attributes);
    }

    public function linkActions($id)
    {
        $methods = ['update', 'delete', 'view'];
        $menu    = \Admin::getMenu();
        $str     = "";
        foreach ($methods as $method) {
            $ucwords    = ucwords($method);
            $function   = "link$ucwords";
            $permission = $this->permission->roleHasPermissionThisMethod("", $method);
            if ($permission == 'method_found') {
                $str .= $this->{$function}($id) . '  ';
            }
        }
        return $str;
    }

    public function stringSaveOrUpdate()
    {
        return \Admin::rawAction() == 'create' ? 'Save' : 'Update';
    }

    public function submitLoading($value = "")
    {
        $value = !empty($value) ? $value : $this->stringSaveOrUpdate();
        $str   = '<button type="submit" class="btn btn-primary btn-loading" id="load" data-loading-text="<i class=\'fa fa-spinner fa-spin\'></i> Loading">' . $value . '</button>';
        return $str;
    }

    public function selectStatus()
    {
        $html = \Form::label('status', 'Status');
        return $html .= \Form::select('status', ['publish' => 'Publish', 'draft' => 'Draft'], null, ['class' => 'form-control']);
    }

    public function childMenuPermission($menu, $cek, $strip = "")
    {
        $tr = "";
        $strip .= $strip . "-";
        foreach ($menu->childs as $child) {
            $tr .= "<tr>
		        <td>$strip $child->label</td>
		        <td>";
            foreach ($child->methods()->where('menu_id', $child->id)->get() as $m) {
                $tr .= \Form::hidden('method_code[]', $m->method) . \Form::hidden('menu_slug[]', $child->slug);
                $tr .= $m->method . ' ' . \Form::checkbox('method[]', $m->pivot->id, $cek($m)) . ' | ';
            }
            $tr .= "</td>
		      </tr>";
            $tr .= $this->childMenuPermission($child, $cek, $strip);
        }

        return $tr;
    }

    public function size_recomendation($size)
    {
        return '<br><small>Size Recomendation  ' . $size . '</small>';
    }
}
