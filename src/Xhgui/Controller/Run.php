<?php

use Slim\Slim;

class Xhgui_Controller_Run extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    private $profiles;

    /**
     * @var Xhgui_WatchFunctions
     */
    private $watches;

    public function __construct(Slim $app, Xhgui_Profiles $profiles, Xhgui_WatchFunctions $watches)
    {
        $this->app = $app;
        $this->profiles = $profiles;
        $this->watches = $watches;
    }

    public function index()
    {
        $request = $this->app->request();

        $search = array();
        $keys = array('date_start', 'date_end', 'url', 'remote_addr', 'server_addr');
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = $request->get($key);
            }
        }
        $sort = $request->get('sort');

        $result = $this->profiles->getAll(array(
            'sort' => $sort,
            'page' => $request->get('page'),
            'direction' => $request->get('direction'),
            'perPage' => $this->app->config('page.limit'),
            'conditions' => $search,
            'projection' => true,
        ));

        $title = '最近运行';
        $titleMap = array(
            'wt' => '执行时间',
            'cpu' => 'CPU时间',
            'mu' => '内存使用',
        );
        if (isset($titleMap[$sort])) {
            $title = $titleMap[$sort];
        }

        $paging = array(
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => $sort,
            'direction' => $result['direction']
        );

        $this->_template = 'runs/list.twig';

        $this->set(array(
            'paging' => $paging,
            'base_url' => 'home',
            'runs' => $result['results'],
            'date_format' => $this->app->config('date.format'),
            'search' => $search,
            'has_search' => strlen(implode('', $search)) > 0,
            'title' => $title
        ));
    }

    public function view()
    {
        $request = $this->app->request();
        $detailCount = $this->app->config('detail.count');
        $result = $this->profiles->get($request->get('id'));

        $result->calculateSelf();

        // Self wall time graph
        $timeChart = $result->extractDimension('ewt', $detailCount);

        foreach($timeChart as $k=>$val){
            $v = round($val['value']/1000,1);
            if($v==0)
            {
                $v = round($val['value']/1000);
            }
            $timeChart[$k]['value'] = $v;
        }

        // Memory Block
        $memoryChart = $result->extractDimension('emu', $detailCount);

        foreach($memoryChart as $k=>$val){
            $v = round($val['value']/1024,1);
            if($v==0)
            {
                $v = round($val['value']/1024);
            }
            $memoryChart[$k]['value'] = $v;
        }

        // Watched Functions Block
        $watchedFunctions = array();
        foreach ($this->watches->getAll() as $watch) {
            $matches = $result->getWatched($watch['name']);
            if ($matches) {
                $watchedFunctions = array_merge($watchedFunctions, $matches);
            }
        }

        $profile = $result->sort('ewt', $result->getProfile());

        $this->_template = 'runs/view.twig';
        $this->set(array(
            'profile' => $profile,
            'result' => $result,
            'wall_time' => $timeChart,
            'memory' => $memoryChart,
            'watches' => $watchedFunctions,
            'date_format' => $this->_app->config('date.format'),
            'title' => '函数监控'
        ));
    }

    public function delete()
    {
        $request = $this->app->request();
        $id = $request->get('id');

        // Delete the profile run.
        $delete = $this->profiles->delete($id);

        $this->app->flash('success', 'Deleted profile ' . $id);

        $referrer = $request->getReferrer();
        // In case route is accessed directly the referrer is not set.
        $redirect = isset($referrer) ? $referrer : $this->app->urlFor('home');
        $this->app->redirect($redirect);
    }

    public function delete_all()
    {
      $request = $this->app->request();

      // Delete all profile runs.
      $delete = $this->profiles->truncate();

      $this->app->flash('success', 'Deleted all profiles');

      $referrer = $request->getReferrer();
      // In case route is accessed directly the referrer is not set.
      $redirect = isset($referrer) ? $referrer : $this->app->urlFor('home');
      $this->app->redirect($redirect);
    }

    public function url()
    {
        $request = $this->app->request();
        $pagination = array(
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction'),
            'page' => $request->get('page'),
            'perPage' => $this->app->config('page.limit'),
        );

        $search = array();
        $keys = array('date_start', 'date_end', 'limit', 'limit_custom');
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }

        $runs = $this->profiles->getForUrl(
            $request->get('url'),
            $pagination,
            $search
        );

        if (isset($search['limit_custom']) && strlen($search['limit_custom']) > 0 && $search['limit_custom'][0] == 'P') {
            $search['limit'] = $search['limit_custom'];
        }

        $chartData = $this->profiles->getPercentileForUrl(
            90,
            $request->get('url'),
            $search
        );

        $paging = array(
            'total_pages' => $runs['totalPages'],
            'sort' => $pagination['sort'],
            'page' => $runs['page'],
            'direction' => $runs['direction']
        );

        $this->_template = 'runs/url.twig';
        $this->set(array(
            'paging' => $paging,
            'base_url' => 'url.view',
            'runs' => $runs['results'],
            'url' => $request->get('url'),
            'chart_data' => $chartData,
            'date_format' => $this->app->config('date.format'),
            'search' => array_merge($search, array('url' => $request->get('url'))),
        ));
    }

    public function compare()
    {
        $request = $this->app->request();

        $baseRun = $headRun = $candidates = $comparison = null;
        $paging = array();

        if ($request->get('base')) {
            $baseRun = $this->profiles->get($request->get('base'));
        }

        if ($baseRun && !$request->get('head')) {
            $pagination = array(
                'direction' => $request->get('direction'),
                'sort' => $request->get('sort'),
                'page' => $request->get('page'),
                'perPage' => $this->app->config('page.limit'),
            );
            $candidates = $this->profiles->getForUrl(
                $baseRun->getMeta('simple_url'),
                $pagination
            );

            $paging = array(
                'total_pages' => $candidates['totalPages'],
                'sort' => $pagination['sort'],
                'page' => $candidates['page'],
                'direction' => $candidates['direction']
            );
        }

        if ($request->get('head')) {
            $headRun = $this->profiles->get($request->get('head'));
        }

        if ($baseRun && $headRun) {
            $comparison = $baseRun->compare($headRun);
        }

        $this->_template = 'runs/compare.twig';
        $this->set(array(
            'base_url' => 'run.compare',
            'base_run' => $baseRun,
            'head_run' => $headRun,
            'candidates' => $candidates,
            'url_params' => $request->get(),
            'date_format' => $this->app->config('date.format'),
            'comparison' => $comparison,
            'paging' => $paging,
            'search' => array(
                'base' => $request->get('base'),
                'head' => $request->get('head'),
            )
        ));
    }

    public function symbol()
    {
        $request = $this->app->request();
        $id = $request->get('id');
        $symbol = $request->get('symbol');

        $profile = $this->profiles->get($id);
        $profile->calculateSelf();
        list($parents, $current, $children) = $profile->getRelatives($symbol);

        $this->_template = 'runs/symbol.twig';
        $this->set(array(
            'symbol' => $symbol,
            'id' => $id,
            'main' => $profile->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ));
    }

    public function symbolShort()
    {
        $request = $this->app->request();
        $id = $request->get('id');
        $threshold = $request->get('threshold');
        $symbol = $request->get('symbol');
        $metric = $request->get('metric');

        $profile = $this->profiles->get($id);
        $profile->calculateSelf();
        list($parents, $current, $children) = $profile->getRelatives($symbol, $metric, $threshold);

        $this->_template = 'runs/symbol-short.twig';
        $this->set(array(
            'symbol' => $symbol,
            'id' => $id,
            'main' => $profile->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ));
    }

    public function callgraph()
    {
        $request = $this->app->request();
        $profile = $this->profiles->get($request->get('id'));

        $this->_template = 'runs/callgraph.twig';
        $this->set(array(
            'profile' => $profile,
            'date_format' => $this->app->config('date.format'),
        ));
    }

    public function callgraphData()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $profile = $this->profiles->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;
        $callgraph = $profile->getCallgraph($metric, $threshold);

        $response['Content-Type'] = 'application/json';
        return $response->body(json_encode($callgraph));
    }

    public function flamegraph()
    {
        $request = $this->app->request();
        $profile = $this->profiles->get($request->get('id'));

        $this->_template = 'runs/flamegraph.twig';
        $this->set(array(
            'profile' => $profile,
            'date_format' => $this->app->config('date.format'),
        ));
    }

    public function flamegraphData()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $profile = $this->profiles->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;
        $flamegraph = $profile->getFlamegraph($metric, $threshold);

        $response['Content-Type'] = 'application/json';
        return $response->body(json_encode($flamegraph));
    }

    public function callgraphDataDot()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $profile = $this->profiles->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;
        $callgraph = $profile->getCallgraphNodes($metric, $threshold);

        $response['Content-Type'] = 'application/json';
        return $response->body(json_encode($callgraph));
    }

    public function import()
    {
        $request = $this->app->request();

        $data = json_decode($request->getBody(), true);

        $container = Xhgui_ServiceContainer::instance();
        /* @var Xhgui_Saver_Mongo $saver */
        $saver = $container['saverMongo'];

        $saver->save($data);
    }
}
