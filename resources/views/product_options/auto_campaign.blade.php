<div class="row">
  <div class="col-lg-6">
    <div class="form-group">
      <label>@lang('product_option.repeat_mailing')</label>
      <select class="form-control repetitions" name="repetitions">
          <option value="0">0 times</option>
          <option value="1">Send 2x  - {{$autoCampaignData['dates'][1]['mailingDate']}}</option>
          <option value="2">Send 3x  - {{$autoCampaignData['dates'][2]['mailingDate']}}</option>
          <option value="3">Send 4x  - {{$autoCampaignData['dates'][3]['mailingDate']}}</option>
          <option value="4">Send 5x  - {{$autoCampaignData['dates'][4]['mailingDate']}}</option>
      </select>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="form-group">
        <label>@lang('product_option.mailing_frequency')</label>
        <select class="form-control"  name="frequncy">
            <option value="1">Weekly</option>
            <option value="2">Every Other Week</option>
            <option value="4">Monthly</option>
        </select>
    </div>
  </div>
  </div>
  <div class="row">
  <div id="legal" class="col-lg-12">
    <div class="form-group">
      <div class="form-check">
          <input class="form-check-input agree-to-terms" type="checkbox" name="agree_to_terms" value="true" {{ ($selectAutoCampaignLegal ? 'checked="checked"' :'') }} required="">
          <label class="form-check-label">I agree to the <a >Terms and Conditions</a></label>
      </div>
    </div>   
  </div>
</div>
