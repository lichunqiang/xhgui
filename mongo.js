//-------------
// Utils
//-------------

/**
 * 获取日期
 *
 * @param dayCount
 * @returns {string}
 */
function getDateStr(dayCount) {
  dayCount = dayCount || 0;
  var dd = new Date();
  dd.setDate(dd.getDate() + dayCount);//获取dayCount天后的日期
  var y = dd.getFullYear();
  var m = dd.getMonth() + 1;//获取当前月份的日期
  var d = dd.getDate();
  return y + "-" + m + "-" + d;
}

/**
 * 定时清理历史数据
 */
function cleanUp() {
  var ds = db.getMongo().getDB('xhprof');

  print(ds.results.remove({'meta.request_date': {'$lt': getDateStr()}}));
}

function test() {
  printjson(db.serverStatus());
}