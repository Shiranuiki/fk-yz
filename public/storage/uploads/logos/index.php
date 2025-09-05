<?php
// 防止直接访问上传目录
http_response_code(403);
exit('Access denied');
