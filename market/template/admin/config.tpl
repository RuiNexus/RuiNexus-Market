<section class="admin-main">
  <div class="container-fluid">
    <div class="page-container">
      <div class="card">
        <div class="card-body">
          <div class="card-title row">
            <div style="padding:0 15px;">{$Title}</div>
            <div class="col-lg-8 col-md-12 col-sm-12">
              {foreach $PluginsAdminMenu as $v}
                {if $v['custom']}
                  <span class="ml-2"><a class="h5" href="{$v.url}" target="_blank">{$v.name}</a></span>
                {else/}
                  <span class="ml-2"><a class="h5" href="{$v.url}">{$v.name}</a></span>
                {/if}
              {/foreach}
            </div>
          </div>
          <div class="help-block">
            配置RuiNexus Market二手服务器交易市场的各项参数
          </div>

          <form method="post" action="{:shd_addon_url('market://AdminIndex/configPost')}" class="needs-validation" novalidate>
            <div class="card-body px-5 mx-auto w-75">
              <div class="row">
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>站点名称</label>
                    <input type="text" class="form-control" name="site_name" value="{$config['site_name'] ?? 'RuiNexus Market'}">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>手续费比例(%)</label>
                    <input type="number" class="form-control" name="fee_percent" value="{$config['fee_percent'] ?? '5'}" min="0" max="100">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>联系邮箱</label>
                    <input type="email" class="form-control" name="contact_email" value="{$config['contact_email'] ?? ''}" placeholder="admin@example.com">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>联系QQ</label>
                    <input type="text" class="form-control" name="contact_qq" value="{$config['contact_qq'] ?? ''}" placeholder="123456789">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>上架审核</label>
                    <select class="form-control" name="need_audit">
                      <option value="1" {if ($config['need_audit'] ?? '1') == '1'}selected{/if}>需要审核</option>
                      <option value="0" {if ($config['need_audit'] ?? '') == '0'}selected{/if}>直接上架</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>线下交易</label>
                    <select class="form-control" name="allow_offline">
                      <option value="1" {if ($config['allow_offline'] ?? '1') == '1'}selected{/if}>允许</option>
                      <option value="0" {if ($config['allow_offline'] ?? '') == '0'}selected{/if}>禁止</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>中间账户UID</label>
                    <input type="number" class="form-control" name="escrow_uid" value="{$config['escrow_uid'] ?? '0'}" min="0" placeholder="0=不启用">
                    <small class="form-text text-muted">上架后自动转移产品到此账户，交易完成转移给买家，下架后退回卖家</small>
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-group">
                    <label>禁止交易的产品ID</label>
                    <input type="text" class="form-control" name="product_blacklist" value="{$config['product_blacklist'] ?? ''}" placeholder="逗号分隔，如: 1,2,3">
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-group">
                    <label>公告内容</label>
                    <textarea rows="4" class="form-control" name="notice_content" placeholder="前端页面公告区域显示的内容">{$config['notice_content'] ?? ''}</textarea>
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary w-xl submitBtn">保存配置</button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.bootcdn.net/ajax/libs/layer/3.5.1/layer.js"></script>
<script type="text/javascript">
$(function () {
  $('form').on('submit', function (e) {
    e.preventDefault();
    var btn = $(this).find('.submitBtn');
    btn.prop('disabled', true).text('保存中...');
    $.ajax({
      type: 'POST',
      url: $(this).attr('action'),
      data: $(this).serialize(),
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, {icon: 1, time: 2000});
        } else {
          layer.msg(res.msg, {icon: 5});
        }
        btn.prop('disabled', false).text('保存配置');
      },
      error: function () {
        layer.msg('保存失败', {icon: 5});
        btn.prop('disabled', false).text('保存配置');
      }
    });
  });
});
</script>
