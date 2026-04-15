<!-- <form id="chat-form" onsubmit="return false;">
  <div id="chat-input">
    <input type="text" id="chat-text" placeholder="Type a message">
    <button type="submit" id="send-btn" class="btn btn-light ms-2">
      <i class="fa-solid fa-paper-plane"></i>
    </button>
  </div>
</form> -->

<form id="chat-form" enctype="multipart/form-data">
    <div class="chat-input-wrapper" style="width: 100%;">

        <!--  ATTACH -->
        <label for="fileInput" class="icon-btn">
            <i class="fa fa-paperclip"></i>
        </label>
        <input type="file" id="fileInput" hidden>

        <!--  TEXT -->
        <input type="text" id="chat-text" placeholder="Type a message...">

        <!--  SEND -->
        <button type="submit" class="send-btn">
            <i class="fa-solid fa-paper-plane"></i>
        </button>

    </div>
</form>

