-- Links a login user to a students row (student portal when no ?studentId= in URL)
ALTER TABLE `users`
  ADD COLUMN `student_id` INT UNSIGNED NULL DEFAULT NULL AFTER `ssid`,
  ADD KEY `users_student_id_idx` (`student_id`);
