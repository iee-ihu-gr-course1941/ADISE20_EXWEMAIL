INSERT IGNORE INTO enums (name) VALUES

('GAME_STATUS_WAITING_PLAYERS'),
('GAME_STATUS_RUNNING'),
('GAME_STATUS_ENDED'),

('GSTATE_FIELD_HOST'), -- Player Id
('GSTATE_FIELD_CURRENT_PLAYER'), -- Player Id
('GSTATE_FIELD_BOARD'), -- JSON array
('GSTATE_FIELD_REMAINING_BONES'), -- JSON array

('PSTATE_FIELD_READY'), -- Boolean
('PSTATE_FIELD_HAND') -- JSON array
