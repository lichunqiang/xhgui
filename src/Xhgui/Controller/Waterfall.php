<?php

use Slim\Slim;

class Xhgui_Controller_Waterfall extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    protected $profiles;

    public function __construct(Slim $app, Xhgui_Profiles $profiles)
    {
        $this->app = $app;
        $this->profiles = $profiles;
    }

    public function index()
    {
        $request = $this->app->request();
        $search = array();
        $keys = array("remote_addr", 'request_start', 'request_end');
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = trim($request->get($key));
            }
        }

        if(isset($search['request_start']) && strpos($search['request_start'],'-')!==false)
        {
            $search['request_start'] = strtotime($search['request_start']);
        }

        if(isset($search['request_end']) && strpos($search['request_end'],'-')!==false)
        {
            $search['request_end'] = strtotime($search['request_end']);
        }

        $result = $this->profiles->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => true
        ));

        $paging = array(
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => 'asc',
            'direction' => $result['direction']
        );

        $this->_template = 'waterfall/list.twig';


        if(isset($search['request_start'])&& strpos($search['request_start'],'-')===false)
        {
            $search['request_start'] = date('Y-m-d H:i:s',$search['request_start']);
        }
        if(isset($search['request_end'])&& strpos($search['request_end'],'-')===false)
        {
            $search['request_end'] = date('Y-m-d H:i:s',$search['request_end']);
        }
        $this->set(array(
            'runs' => $result['results'],
            'search' => $search,
            'paging' => $paging,
            'base_url' => 'waterfall.list',
            'title' => '瀑布图',
            'date_format' => $this->app->config('date.format')
        ));
    }

    public function query()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $search = array();
        $keys = array("remote_addr", 'request_start', 'request_end');
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }
        $result = $this->profiles->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => TRUE
        ));
        $datas = array();
        foreach ($result['results'] as $r) {
            $duration = $r->get('main()', 'wt');
            $start = $r->getMeta('SERVER.REQUEST_TIME_FLOAT');
            $title = $r->getMeta('url');
            $datas[] = array(
                'id' => (string)$r->getId(),
                'title' => $title,
                'start' => $start * 1000,
                'duration' => $duration / 1000 // Convert to correct scale
            );
        }
        $response->body(json_encode($datas));
        $response['Content-Type'] = 'application/json';
    }

}
