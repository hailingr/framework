<?php
class AdminTableController extends Controller
{
    public function index()
    {
        $this->template('admin.table.list');
    }
}