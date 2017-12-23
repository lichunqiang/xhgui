Setup
------

### 创建mongo索引

```
$ mongo
> use xhprof
> db.results.ensureIndex( { 'meta.SERVER.REQUEST_TIME' : -1 } )
> db.results.ensureIndex( { 'profile.main().wt' : -1 } )
> db.results.ensureIndex( { 'profile.main().mu' : -1 } )
> db.results.ensureIndex( { 'profile.main().cpu' : -1 } )
> db.results.ensureIndex( { 'meta.url' : 1 } )
> db.results.ensureIndex( { 'meta.simple_url' : 1 } )
> db.results.ensureIndex( { 'meta.request_ts' : 1 } )
> db.results.ensureIndex( { 'meta.SERVER.REMOTE_ADDR' : 1 } )
> db.results.ensureIndex( { 'meta.SERVER.SERVER_ADD' : 1 } )
```

查看创建索引情况:

```
> use mongo
> use xhprof
> db.results.getIndexes()
```

### 定时清理数据

